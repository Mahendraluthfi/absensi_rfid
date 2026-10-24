<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCategory;
use App\Models\AttendanceLog;
use App\Models\SchoolSetting;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceScanController extends Controller
{
    /**
     * Display the RFID Attendance Scanner Kiosk page.
     */
    public function index(): Response
    {
        $today = Carbon::today()->toDateString();
        $nowTime = Carbon::now()->format('H:i:s');
        $settings = SchoolSetting::current();
        $categories = AttendanceCategory::active()
            ->whereNotNull('jam_mulai')
            ->orderBy('jam_mulai')
            ->get();

        // Determine currently active automatic category
        $activeCategory = $this->resolveActiveCategory($categories, $nowTime);

        // Today's logs
        $todayLogs = AttendanceLog::with(['student', 'category'])
            ->whereDate('tanggal', $today)
            ->latest('id')
            ->get();

        $recentScans = $todayLogs->take(15)->map(function ($log) {
            return [
                'id' => $log->id,
                'student_id' => $log->student_id,
                'student_name' => $log->student?->nama ?? 'Siswa Tidak Diketahui',
                'student_nisn' => $log->student?->nisn ?? '-',
                'student_class' => $log->student?->kelas ?? '-',
                'student_photo' => $log->student?->foto,
                'category_name' => $log->category?->nama ?? 'Hadir',
                'category_code' => $log->category?->kode ?? 'HADIR',
                'category_color' => $log->category?->warna ?? 'emerald',
                'tipe' => $log->tipe,
                'waktu' => substr($log->waktu, 0, 5),
                'menit_terlambat' => $log->menit_terlambat,
                'created_at_human' => $log->created_at->diffForHumans(),
            ];
        });

        // Test students with RFID for the simulator bar
        $studentsWithRfid = Student::active()
            ->whereNotNull('rfid_uid')
            ->select('id', 'nama', 'nisn', 'kelas', 'rfid_uid', 'foto')
            ->orderBy('kelas')
            ->orderBy('nama')
            ->take(15)
            ->get();

        // All active students for manual lookup
        $allActiveStudents = Student::active()
            ->select('id', 'nama', 'nisn', 'kelas', 'rfid_uid', 'foto')
            ->orderBy('kelas')
            ->orderBy('nama')
            ->get();

        // Stats by category today
        $categoryStats = [];
        foreach ($categories as $cat) {
            $categoryStats[] = [
                'id' => $cat->id,
                'nama' => $cat->nama,
                'kode' => $cat->kode,
                'warna' => $cat->warna,
                'total' => $todayLogs->where('category_id', $cat->id)->count(),
            ];
        }

        return Inertia::render('Attendance/Scan', [
            'settings' => $settings,
            'categories' => $categories,
            'active_category' => $activeCategory,
            'today_formatted' => Carbon::now()->isoFormat('dddd, D MMMM Y'),
            'current_time' => Carbon::now()->format('H:i:s'),
            'recent_scans' => $recentScans,
            'students_with_rfid' => $studentsWithRfid,
            'all_students' => $allActiveStudents,
            'stats' => [
                'total_tap_today' => $todayLogs->count(),
                'unique_students' => $todayLogs->pluck('student_id')->unique()->count(),
                'by_category' => $categoryStats,
            ],
        ]);
    }

    /**
     * Process an incoming RFID tap.
     */
    public function scan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rfid_uid' => ['required', 'string', 'max:50'],
            'category_id' => ['nullable'], // 'auto' or numeric category ID
        ]);

        $rfidUid = trim($validated['rfid_uid']);
        $selectedCategoryId = $validated['category_id'] ?? 'auto';

        $today = Carbon::today()->toDateString();
        $now = Carbon::now();
        $nowTime = $now->format('H:i:s');

        // 1. Check if student exists with this RFID
        $student = Student::where('rfid_uid', $rfidUid)->first();

        if (! $student) {
            return response()->json([
                'success' => false,
                'status' => 'unregistered',
                'message' => "Kartu RFID [{$rfidUid}] belum terdaftar pada siswa mana pun!",
                'rfid_uid' => $rfidUid,
            ], 404);
        }

        if ($student->status !== 'aktif') {
            return response()->json([
                'success' => false,
                'status' => 'inactive',
                'message' => "Siswa {$student->nama} saat ini berstatus non-aktif.",
                'student' => $student,
            ], 422);
        }

        // 2. Resolve attendance category (Dynamic Session)
        $category = null;

        if ($selectedCategoryId && $selectedCategoryId !== 'auto' && is_numeric($selectedCategoryId)) {
            $category = AttendanceCategory::active()->find($selectedCategoryId);
        }

        if (! $category) {
            // Mode Otomatis: cari kategori berdasarkan rentang jam sekarang
            $activeCategories = AttendanceCategory::active()
                ->whereNotNull('jam_mulai')
                ->orderBy('jam_mulai')
                ->get();

            $category = $this->resolveActiveCategory($activeCategories, $nowTime);

            if (! $category) {
                // If no session matches exact time window, pick fallback default
                $category = AttendanceCategory::active()->first();
            }
        }

        if (! $category) {
            return response()->json([
                'success' => false,
                'status' => 'no_session',
                'message' => "Tidak ada sesi kategori absensi aktif pada jam {$nowTime} WIB. Silakan atur kategori absensi.",
            ], 422);
        }

        // 3. Prevent duplicate scan for the SAME category on the SAME day
        $existingLog = AttendanceLog::with('category')
            ->where('student_id', $student->id)
            ->where('category_id', $category->id)
            ->whereDate('tanggal', $today)
            ->first();

        if ($existingLog) {
            $existingTime = substr($existingLog->waktu, 0, 5);

            return response()->json([
                'success' => false,
                'status' => 'duplicate',
                'message' => "Siswa {$student->nama} ({$student->kelas}) sudah melakukan {$category->nama} hari ini pada pukul {$existingTime} WIB!",
                'student' => $student,
                'category' => $category,
                'log' => $existingLog,
            ], 422);
        }

        // 4. Calculate lateness if jam_selesai is defined on this category
        $menitTerlambat = 0;
        $isLate = false;

        if ($category->jam_selesai && $nowTime > $category->jam_selesai) {
            $batasTime = Carbon::createFromTimeString($category->jam_selesai);
            $menitTerlambat = max(1, (int) round($batasTime->diffInMinutes($now, false)));
            $isLate = true;
            $message = "Presensi {$category->nama} Berhasil! Terlambat {$menitTerlambat} menit.";
        } else {
            $message = "Presensi {$category->nama} Berhasil! Tepat waktu.";
        }

        // 5. Store attendance log
        $log = AttendanceLog::create([
            'student_id' => $student->id,
            'category_id' => $category->id,
            'rfid_uid' => $rfidUid,
            'tanggal' => $today,
            'waktu' => $nowTime,
            'tipe' => $category->tipe,
            'metode' => 'rfid',
            'menit_terlambat' => $menitTerlambat,
            'keterangan' => $message,
        ]);

        $log->load(['student', 'category']);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'is_late' => $isLate,
            'menit_terlambat' => $menitTerlambat,
            'message' => $message,
            'student' => $student,
            'category' => $category,
            'waktu_formatted' => substr($nowTime, 0, 5),
            'log' => [
                'id' => $log->id,
                'student_id' => $student->id,
                'student_name' => $student->nama,
                'student_nisn' => $student->nisn,
                'student_class' => $student->kelas,
                'student_photo' => $student->foto,
                'category_name' => $category->nama,
                'category_code' => $category->kode,
                'category_color' => $category->warna,
                'tipe' => $log->tipe,
                'waktu' => substr($log->waktu, 0, 5),
                'menit_terlambat' => $menitTerlambat,
                'created_at_human' => 'Baru saja',
            ],
        ]);
    }

    /**
     * Resolve active category based on current wall-clock time.
     */
    private function resolveActiveCategory($categories, string $nowTime): ?AttendanceCategory
    {
        // 1. Look for category where nowTime is between jam_mulai and toleransi_terlambat (or jam_selesai)
        foreach ($categories as $cat) {
            if (! $cat->jam_mulai) {
                continue;
            }

            $endTime = $cat->toleransi_terlambat ?: $cat->jam_selesai;

            if ($cat->jam_mulai <= $nowTime && $endTime && $nowTime <= $endTime) {
                return $cat;
            }
        }

        // 2. If exactly at boundaries or before first session, pick the first morning session
        $firstSession = $categories->first();
        if ($firstSession && $nowTime < ($firstSession->toleransi_terlambat ?: $firstSession->jam_selesai)) {
            return $firstSession;
        }

        // 3. Fallback to category that starts earliest or current closest
        return $categories->first();
    }

    /**
     * Manual attendance entry.
     */
    public function manual(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'category_id' => ['required', 'exists:attendance_categories,id'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $category = AttendanceCategory::findOrFail($validated['category_id']);
        $today = Carbon::today()->toDateString();
        $nowTime = Carbon::now()->format('H:i:s');

        // Check duplicate
        $existing = AttendanceLog::where('student_id', $student->id)
            ->where('category_id', $category->id)
            ->whereDate('tanggal', $today)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => "Siswa {$student->nama} sudah memiliki catatan {$category->nama} hari ini.",
            ], 422);
        }

        $log = AttendanceLog::create([
            'student_id' => $student->id,
            'category_id' => $category->id,
            'rfid_uid' => $student->rfid_uid,
            'tanggal' => $today,
            'waktu' => $nowTime,
            'tipe' => $category->tipe,
            'metode' => 'manual',
            'menit_terlambat' => 0,
            'keterangan' => $validated['keterangan'] ?: "Presensi manual: {$category->nama}",
        ]);

        return response()->json([
            'success' => true,
            'message' => "Presensi manual {$category->nama} untuk {$student->nama} berhasil dicatat.",
            'log' => $log,
        ]);
    }
}
