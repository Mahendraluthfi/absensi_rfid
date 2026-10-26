<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolClassTest extends TestCase
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
    }

    public function test_admin_can_view_classes_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('classes.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_create_new_class(): void
    {
        $response = $this->actingAs($this->admin)->post(route('classes.store'), [
            'nama' => 'VII D',
            'tingkat' => 7,
            'urutan' => 10,
            'keterangan' => 'Kelas Baru',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('school_classes', [
            'nama' => 'VII D',
            'tingkat' => 7,
        ]);
    }

    public function test_admin_can_update_class_and_student_kelas_is_synced(): void
    {
        $class = SchoolClass::create([
            'nama' => 'VII Old',
            'tingkat' => 7,
            'urutan' => 1,
            'is_active' => true,
        ]);

        $student = Student::create([
            'nisn' => '7777000111',
            'nama' => 'Siswa Sync Test',
            'school_class_id' => $class->id,
            'kelas' => 'VII Old',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($this->admin)->put(route('classes.update', $class->id), [
            'nama' => 'VII New Name',
            'tingkat' => 7,
            'urutan' => 1,
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('school_classes', [
            'id' => $class->id,
            'nama' => 'VII New Name',
        ]);

        // Verify student string 'kelas' was synced automatically
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'kelas' => 'VII New Name',
        ]);
    }

    public function test_admin_cannot_delete_class_with_students(): void
    {
        $class = SchoolClass::create([
            'nama' => 'VIII Protected',
            'tingkat' => 8,
            'is_active' => true,
        ]);

        Student::create([
            'nisn' => '7777000222',
            'nama' => 'Protected Student',
            'school_class_id' => $class->id,
            'kelas' => 'VIII Protected',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('classes.destroy', $class->id));
        $response->assertRedirect();

        // Class should still exist
        $this->assertDatabaseHas('school_classes', [
            'id' => $class->id,
        ]);
    }

    public function test_admin_can_delete_empty_class(): void
    {
        $class = SchoolClass::create([
            'nama' => 'Empty Class To Delete',
            'tingkat' => 8,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('classes.destroy', $class->id));
        $response->assertRedirect();

        $this->assertDatabaseMissing('school_classes', [
            'id' => $class->id,
        ]);
    }

    public function test_admin_can_get_students_by_class_json(): void
    {
        $class = SchoolClass::create([
            'nama' => 'VII Json Test',
            'tingkat' => 7,
            'is_active' => true,
        ]);

        Student::create([
            'nisn' => '7777000333',
            'nama' => 'Json Student',
            'school_class_id' => $class->id,
            'kelas' => 'VII Json Test',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('classes.students', $class->id));
        $response->assertStatus(200);
        $response->assertJsonStructure(['class', 'students', 'count']);
        $response->assertJsonCount(1, 'students');
    }

    public function test_admin_can_promote_students_to_another_class(): void
    {
        $classA = SchoolClass::create([
            'nama' => 'VII Origin',
            'tingkat' => 7,
            'is_active' => true,
        ]);

        $classB = SchoolClass::create([
            'nama' => 'VIII Destination',
            'tingkat' => 8,
            'is_active' => true,
        ]);

        $student1 = Student::create([
            'nisn' => '7777000441',
            'nama' => 'Student Naik 1',
            'school_class_id' => $classA->id,
            'kelas' => 'VII Origin',
            'status' => 'aktif',
        ]);

        $student2 = Student::create([
            'nisn' => '7777000442',
            'nama' => 'Student Naik 2',
            'school_class_id' => $classA->id,
            'kelas' => 'VII Origin',
            'status' => 'aktif',
        ]);

        $studentStay = Student::create([
            'nisn' => '7777000443',
            'nama' => 'Student Tinggal Kelas',
            'school_class_id' => $classA->id,
            'kelas' => 'VII Origin',
            'status' => 'aktif',
        ]);

        // Promote only student1 and student2
        $response = $this->actingAs($this->admin)->post(route('classes.promote'), [
            'action' => 'promote',
            'source_class_id' => $classA->id,
            'target_class_id' => $classB->id,
            'student_ids' => [$student1->id, $student2->id],
        ]);

        $response->assertRedirect();

        // Student 1 & 2 should now be in Class B
        $this->assertDatabaseHas('students', [
            'id' => $student1->id,
            'school_class_id' => $classB->id,
            'kelas' => 'VIII Destination',
        ]);
        $this->assertDatabaseHas('students', [
            'id' => $student2->id,
            'school_class_id' => $classB->id,
            'kelas' => 'VIII Destination',
        ]);

        // Student Stay should remain in Class A
        $this->assertDatabaseHas('students', [
            'id' => $studentStay->id,
            'school_class_id' => $classA->id,
            'kelas' => 'VII Origin',
        ]);
    }

    public function test_admin_can_graduate_students_and_unpair_rfid(): void
    {
        $classIX = SchoolClass::create([
            'nama' => 'IX Senior',
            'tingkat' => 9,
            'is_active' => true,
        ]);

        $student = Student::create([
            'nisn' => '7777000555',
            'nama' => 'Senior Student',
            'school_class_id' => $classIX->id,
            'kelas' => 'IX Senior',
            'rfid_uid' => 'CARD_SENIOR_999',
            'status' => 'aktif',
        ]);

        // Graduate student
        $response = $this->actingAs($this->admin)->post(route('classes.promote'), [
            'action' => 'graduate',
            'source_class_id' => $classIX->id,
            'student_ids' => [$student->id],
        ]);

        $response->assertRedirect();

        // Verify status becomes 'lulus' and rfid_uid becomes null
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'status' => 'lulus',
            'rfid_uid' => null,
        ]);
    }
}

