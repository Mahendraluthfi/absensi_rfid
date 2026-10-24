<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCategory;
use App\Models\AttendanceLog;
use App\Models\SchoolSetting;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with stats and activity.
     */
    public function index(Request $request): Response
    {
        $today = Carbon::today()->toDateString();
        $totalStudents = Student::active()->count();
        $totalRfidAssigned = Student::active()->whereNotNull('rfid_uid')->count();

        // Today's logs
        $todayLogs = AttendanceLog::with(['student', 'category'])
            ->whereDate('tanggal', $today)
            ->get();

        // Unique students present today (check-in)
        $presentStudentIds = $todayLogs->where('tipe', 'masuk')->pluck('student_id')->unique();
        $todayPresentCount = $presentStudentIds->count();

        // Late students today
        $lateCategory = AttendanceCategory::where('kode', 'TERLAMBAT')->first();
        $todayLateCount = $lateCategory
            ? $todayLogs->where('category_id', $lateCategory->id)->pluck('student_id')->unique()->count()
            : 0;

        // On time students
        $onTimeCategory = AttendanceCategory::where('kode', 'HADIR')->first();
        $todayOnTimeCount = $onTimeCategory
            ? $todayLogs->where('category_id', $onTimeCategory->id)->pluck('student_id')->unique()->count()
            : 0;

        // Absent / not yet scanned
        $todayAbsentCount = max(0, $totalStudents - $todayPresentCount);

        // Attendance percentage
        $attendancePercentage = $totalStudents > 0
            ? round(($todayPresentCount / $totalStudents) * 100, 1)
            : 0;

        // Class breakdown
        $classes = Student::active()->select('kelas')->distinct()->orderBy('kelas')->pluck('kelas');
        $classStats = [];
        foreach ($classes as $kelas) {
            $classTotal = Student::active()->where('kelas', $kelas)->count();
            $classStudentIds = Student::active()->where('kelas', $kelas)->pluck('id');
            $classPresent = $todayLogs->where('tipe', 'masuk')->whereIn('student_id', $classStudentIds)->pluck('student_id')->unique()->count();
            $classPercent = $classTotal > 0 ? round(($classPresent / $classTotal) * 100, 1) : 0;

            $classStats[] = [
                'kelas' => $kelas,
                'total' => $classTotal,
                'hadir' => $classPresent,
                'persentase' => $classPercent,
            ];
        }

        // Recent 10 scans
        $recentScans = AttendanceLog::with(['student', 'category'])
            ->whereDate('tanggal', $today)
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'student_name' => $log->student?->nama ?? 'Siswa Tidak Diketahui',
                    'student_nisn' => $log->student?->nisn ?? '-',
                    'student_class' => $log->student?->kelas ?? '-',
                    'category_name' => $log->category?->nama ?? 'Hadir',
                    'category_code' => $log->category?->kode ?? 'HADIR',
                    'category_color' => $log->category?->warna ?? 'emerald',
                    'tipe' => $log->tipe,
                    'waktu' => substr($log->waktu, 0, 5),
                    'menit_terlambat' => $log->menit_terlambat,
                    'created_at_human' => $log->created_at->diffForHumans(),
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_students' => $totalStudents,
                'total_rfid_assigned' => $totalRfidAssigned,
                'today_present' => $todayPresentCount,
                'today_on_time' => $todayOnTimeCount,
                'today_late' => $todayLateCount,
                'today_absent' => $todayAbsentCount,
                'attendance_percentage' => $attendancePercentage,
            ],
            'class_stats' => $classStats,
            'recent_scans' => $recentScans,
            'settings' => SchoolSetting::current(),
            'today_formatted' => Carbon::now()->isoFormat('dddd, D MMMM Y'),
        ]);
    }
}
