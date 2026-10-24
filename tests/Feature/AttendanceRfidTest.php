<?php

namespace Tests\Feature;

use App\Models\AttendanceCategory;
use App\Models\AttendanceLog;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRfidTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin@absensi.local'],
            ['name' => 'Admin', 'password' => bcrypt('admin123')]
        );

        SchoolSetting::current();

        AttendanceCategory::firstOrCreate(
            ['kode' => 'ABSEN_PAGI'],
            [
                'nama' => 'Absen Berangkat',
                'tipe' => 'masuk',
                'jam_mulai' => '06:00:00',
                'jam_selesai' => '07:00:00',
                'toleransi_terlambat' => '08:30:00',
                'warna' => 'emerald',
            ]
        );

        AttendanceCategory::firstOrCreate(
            ['kode' => 'SHOLAT_DHUHUR'],
            [
                'nama' => 'Absen Sholat Dhuhur',
                'tipe' => 'ibadah',
                'jam_mulai' => '11:30:00',
                'jam_selesai' => '12:00:00',
                'toleransi_terlambat' => '13:00:00',
                'warna' => 'indigo',
            ]
        );

        AttendanceCategory::firstOrCreate(
            ['kode' => 'ABSEN_PULANG'],
            [
                'nama' => 'Absen Pulang',
                'tipe' => 'pulang',
                'jam_mulai' => '14:00:00',
                'jam_selesai' => '15:00:00',
                'toleransi_terlambat' => '17:00:00',
                'warna' => 'blue',
            ]
        );
    }

    public function test_students_can_be_retrieved(): void
    {
        $response = $this->actingAs($this->admin)->get(route('students.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_pair_rfid_card_to_student(): void
    {
        $student = Student::firstOrCreate(
            ['nisn' => '9999000111'],
            ['nama' => 'Test Siswa RFID', 'kelas' => 'VII', 'status' => 'aktif']
        );

        $response = $this->actingAs($this->admin)->postJson(route('students.pair-rfid'), [
            'student_id' => $student->id,
            'rfid_uid' => 'CARD998877',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'rfid_uid' => 'CARD998877',
        ]);
    }

    public function test_cannot_pair_same_rfid_to_another_student(): void
    {
        $student1 = Student::firstOrCreate(
            ['nisn' => '9999000222'],
            ['nama' => 'Siswa 1', 'kelas' => 'VII', 'rfid_uid' => 'DUPLICATE_CARD', 'status' => 'aktif']
        );

        $student2 = Student::firstOrCreate(
            ['nisn' => '9999000333'],
            ['nama' => 'Siswa 2', 'kelas' => 'VII', 'status' => 'aktif']
        );

        $response = $this->actingAs($this->admin)->postJson(route('students.pair-rfid'), [
            'student_id' => $student2->id,
            'rfid_uid' => 'DUPLICATE_CARD',
        ]);

        $response->assertStatus(422);
    }

    public function test_scan_terminal_auto_detects_morning_session_on_time(): void
    {
        $student = Student::firstOrCreate(
            ['nisn' => '9999000444'],
            ['nama' => 'Siswa Test Scan', 'kelas' => 'VII', 'rfid_uid' => 'SCAN_VALID_123', 'status' => 'aktif']
        );

        AttendanceLog::where('student_id', $student->id)->delete();

        // 06:45 AM -> within ABSEN_PAGI (06:00 - 07:00)
        Carbon::setTestNow(Carbon::parse('2026-10-22 06:45:00'));

        $response = $this->postJson(route('attendance.scan.post'), [
            'rfid_uid' => 'SCAN_VALID_123',
            'category_id' => 'auto',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'success',
            'menit_terlambat' => 0,
        ]);

        $this->assertDatabaseHas('attendance_logs', [
            'student_id' => $student->id,
            'tipe' => 'masuk',
            'menit_terlambat' => 0,
        ]);
    }

    public function test_scan_after_deadline_marks_as_late_with_correct_minutes(): void
    {
        $student = Student::firstOrCreate(
            ['nisn' => '9999000555'],
            ['nama' => 'Siswa Test Telat', 'kelas' => 'VIII A', 'rfid_uid' => 'SCAN_LATE_123', 'status' => 'aktif']
        );

        AttendanceLog::where('student_id', $student->id)->delete();

        // 07:18 AM -> within ABSEN_PAGI tolerance (07:00 - 08:30)
        Carbon::setTestNow(Carbon::parse('2026-10-22 07:18:00'));

        $response = $this->postJson(route('attendance.scan.post'), [
            'rfid_uid' => 'SCAN_LATE_123',
            'category_id' => 'auto',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'success',
            'is_late' => true,
            'menit_terlambat' => 18,
        ]);

        $this->assertDatabaseHas('attendance_logs', [
            'student_id' => $student->id,
            'tipe' => 'masuk',
            'menit_terlambat' => 18,
        ]);
    }

    public function test_scan_auto_detects_sholat_dhuhur_at_noon(): void
    {
        $student = Student::firstOrCreate(
            ['nisn' => '9999000777'],
            ['nama' => 'Siswa Sholat Dhuhur', 'kelas' => 'VIII B', 'rfid_uid' => 'SCAN_DHUHUR_123', 'status' => 'aktif']
        );

        AttendanceLog::where('student_id', $student->id)->delete();

        // 11:45 AM -> within SHOLAT_DHUHUR (11:30 - 13:00)
        Carbon::setTestNow(Carbon::parse('2026-10-22 11:45:00'));

        $response = $this->postJson(route('attendance.scan.post'), [
            'rfid_uid' => 'SCAN_DHUHUR_123',
            'category_id' => 'auto',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'success',
            'category' => [
                'kode' => 'SHOLAT_DHUHUR',
            ],
        ]);
    }

    public function test_duplicate_scan_on_same_category_is_rejected(): void
    {
        $student = Student::firstOrCreate(
            ['nisn' => '9999000666'],
            ['nama' => 'Siswa Test Double', 'kelas' => 'IX A', 'rfid_uid' => 'SCAN_DOUBLE_123', 'status' => 'aktif']
        );

        AttendanceLog::where('student_id', $student->id)->delete();

        Carbon::setTestNow(Carbon::parse('2026-10-22 06:40:00'));

        // First scan
        $this->postJson(route('attendance.scan.post'), [
            'rfid_uid' => 'SCAN_DOUBLE_123',
            'category_id' => 'auto',
        ])->assertStatus(200);

        // Second immediate scan
        $response2 = $this->postJson(route('attendance.scan.post'), [
            'rfid_uid' => 'SCAN_DOUBLE_123',
            'category_id' => 'auto',
        ]);

        $response2->assertStatus(422);
        $response2->assertJson([
            'success' => false,
            'status' => 'duplicate',
        ]);
    }

    public function test_unregistered_card_returns_404(): void
    {
        $response = $this->postJson(route('attendance.scan.post'), [
            'rfid_uid' => 'CARD_UNKNOWN_9999',
            'category_id' => 'auto',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'status' => 'unregistered',
        ]);
    }

    public function test_attendance_report_excel_export(): void
    {
        $response = $this->actingAs($this->admin)->get(route('reports.export'));
        $response->assertStatus(200);
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('content-type'));
    }

    public function test_admin_can_update_student_with_empty_rfid_and_phone(): void
    {
        $student = Student::firstOrCreate(
            ['nisn' => '8888000111'],
            ['nama' => 'Student Edit Test', 'kelas' => 'VIII A', 'status' => 'aktif']
        );

        $response = $this->actingAs($this->admin)->put(route('students.update', $student->id), [
            'nisn' => '8888000111',
            'nama' => 'Student Edit Test Updated',
            'kelas' => 'VIII A',
            'rfid_uid' => '',
            'no_telepon' => '',
            'status' => 'aktif',
            'jenis_kelamin' => 'L',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'nama' => 'Student Edit Test Updated',
            'rfid_uid' => null,
            'no_telepon' => null,
        ]);
    }

    public function test_admin_can_update_category_with_empty_time_fields(): void
    {
        $category = AttendanceCategory::firstOrCreate(
            ['kode' => 'EKSTRAKURIKULER'],
            ['nama' => 'Ekskul Sore', 'tipe' => 'kegiatan', 'warna' => 'purple']
        );

        $response = $this->actingAs($this->admin)->put(route('categories.update', $category->id), [
            'kode' => 'EKSTRAKURIKULER',
            'nama' => 'Ekskul Sore Edit',
            'tipe' => 'kegiatan',
            'jam_mulai' => '',
            'jam_selesai' => '',
            'toleransi_terlambat' => '',
            'warna' => 'purple',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('attendance_categories', [
            'id' => $category->id,
            'nama' => 'Ekskul Sore Edit',
            'jam_mulai' => null,
            'jam_selesai' => null,
            'toleransi_terlambat' => null,
        ]);
    }
}
