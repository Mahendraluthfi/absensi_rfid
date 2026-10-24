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
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceReportController extends Controller
{
    /**
     * Display the Attendance Reports page.
     */
    public function index(Request $request): Response
    {
        $today = Carbon::today()->toDateString();
        $dateFrom = $request->query('date_from', Carbon::now()->startOfMonth()->toDateString());
        $dateTo = $request->query('date_to', $today);
        $kelas = $request->query('kelas', 'Semua');
        $categoryId = $request->query('category_id', 'all');
        $search = $request->query('search');

        // Detailed logs query
        $logQuery = AttendanceLog::with(['student', 'category'])
            ->whereBetween('tanggal', [$dateFrom, $dateTo]);

        if ($kelas !== 'Semua') {
            $logQuery->whereHas('student', function ($q) use ($kelas) {
                $q->where('kelas', $kelas);
            });
        }

        if ($categoryId !== 'all') {
            $logQuery->where('category_id', $categoryId);
        }

        if ($search) {
            $logQuery->whereHas('student', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        $logs = $logQuery->orderBy('tanggal', 'desc')
            ->orderBy('waktu', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Summary counts for filtered date range
        $allRangeLogs = AttendanceLog::whereBetween('tanggal', [$dateFrom, $dateTo])
            ->when($kelas !== 'Semua', function ($q) use ($kelas) {
                $q->whereHas('student', fn ($s) => $s->where('kelas', $kelas));
            })
            ->get();

        $hadirCategory = AttendanceCategory::where('kode', 'HADIR')->first();
        $terlambatCategory = AttendanceCategory::where('kode', 'TERLAMBAT')->first();
        $sakitCategory = AttendanceCategory::where('kode', 'SAKIT')->first();
        $izinCategory = AttendanceCategory::where('kode', 'IZIN')->first();

        $summary = [
            'total_logs' => $allRangeLogs->count(),
            'total_on_time' => $hadirCategory ? $allRangeLogs->where('category_id', $hadirCategory->id)->count() : 0,
            'total_late' => $terlambatCategory ? $allRangeLogs->where('category_id', $terlambatCategory->id)->count() : 0,
            'total_sick' => $sakitCategory ? $allRangeLogs->where('category_id', $sakitCategory->id)->count() : 0,
            'total_permission' => $izinCategory ? $allRangeLogs->where('category_id', $izinCategory->id)->count() : 0,
        ];

        // Monthly Matrix data preparation
        $month = $request->query('month', Carbon::now()->month);
        $year = $request->query('year', Carbon::now()->year);

        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $matrixClass = $kelas === 'Semua' ? 'VII' : $kelas;
        $matrixStudents = Student::where('status', 'aktif')
            ->where('kelas', $matrixClass)
            ->orderBy('nama')
            ->get();

        $matrixLogs = AttendanceLog::with('category')
            ->whereBetween('tanggal', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->whereIn('student_id', $matrixStudents->pluck('id'))
            ->get();

        $daysInMonth = $startOfMonth->daysInMonth;
        $matrixData = [];

        foreach ($matrixStudents as $student) {
            $studentLogs = $matrixLogs->where('student_id', $student->id);
            $dailyStatus = [];
            $totalHadir = 0;
            $totalTerlambat = 0;
            $totalSakit = 0;
            $totalIzin = 0;
            $totalAlpa = 0;

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $logDay = $studentLogs->firstWhere('tanggal', Carbon::parse($dateStr));

                if ($logDay) {
                    $code = strtoupper(substr($logDay->category?->kode ?? 'H', 0, 1));
                    $color = $logDay->category?->warna ?? 'emerald';
                    $dailyStatus[$day] = [
                        'code' => $code,
                        'color' => $color,
                        'category' => $logDay->category?->nama,
                        'waktu' => substr($logDay->waktu, 0, 5),
                        'late' => $logDay->menit_terlambat,
                    ];

                    if ($code === 'H') {
                        $totalHadir++;
                    } elseif ($code === 'T') {
                        $totalTerlambat++;
                    } elseif ($code === 'S') {
                        $totalSakit++;
                    } elseif ($code === 'I') {
                        $totalIzin++;
                    }
                } else {
                    $isWeekend = Carbon::parse($dateStr)->isWeekend();
                    $dailyStatus[$day] = [
                        'code' => $isWeekend ? '-' : '',
                        'color' => 'gray',
                        'category' => $isWeekend ? 'Libur' : 'Belum Absen',
                        'waktu' => null,
                        'late' => 0,
                    ];
                }
            }

            $matrixData[] = [
                'id' => $student->id,
                'nisn' => $student->nisn,
                'nama' => $student->nama,
                'kelas' => $student->kelas,
                'daily' => $dailyStatus,
                'totals' => [
                    'hadir' => $totalHadir,
                    'terlambat' => $totalTerlambat,
                    'sakit' => $totalSakit,
                    'izin' => $totalIzin,
                    'alpa' => $totalAlpa,
                ],
            ];
        }

        $classes = Student::select('kelas')->distinct()->orderBy('kelas')->pluck('kelas');
        $categories = AttendanceCategory::all();

        return Inertia::render('Reports/Index', [
            'logs' => $logs,
            'summary' => $summary,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'kelas' => $kelas,
                'category_id' => $categoryId,
                'search' => $search,
                'month' => (int) $month,
                'year' => (int) $year,
            ],
            'classes' => $classes,
            'categories' => $categories,
            'matrix' => [
                'class' => $matrixClass,
                'month' => (int) $month,
                'month_name' => Carbon::createFromDate($year, $month, 1)->isoFormat('MMMM Y'),
                'year' => (int) $year,
                'days_count' => $daysInMonth,
                'students' => $matrixData,
            ],
            'settings' => SchoolSetting::current(),
        ]);
    }

    /**
     * Export attendance logs to Excel file.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $dateFrom = $request->query('date_from', Carbon::now()->startOfMonth()->toDateString());
        $dateTo = $request->query('date_to', Carbon::today()->toDateString());
        $kelas = $request->query('kelas', 'Semua');
        $categoryId = $request->query('category_id', 'all');

        $logQuery = AttendanceLog::with(['student', 'category'])
            ->whereBetween('tanggal', [$dateFrom, $dateTo]);

        if ($kelas !== 'Semua') {
            $logQuery->whereHas('student', fn ($s) => $s->where('kelas', $kelas));
        }

        if ($categoryId !== 'all') {
            $logQuery->where('category_id', $categoryId);
        }

        $logs = $logQuery->orderBy('tanggal', 'desc')->orderBy('waktu', 'desc')->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Absensi');

        // Title rows
        $settings = SchoolSetting::current();
        $sheet->setCellValue('A1', strtoupper($settings->nama_sekolah));
        $sheet->setCellValue('A2', "LAPORAN KEHADIRAN SISWA PERIODE {$dateFrom} S/D {$dateTo} (KELAS: {$kelas})");
        $sheet->getStyle('A1:A2')->getFont()->setBold(true);

        // Header Table
        $sheet->setCellValue('A4', 'NO.');
        $sheet->setCellValue('B4', 'TANGGAL');
        $sheet->setCellValue('C4', 'WAKTU');
        $sheet->setCellValue('D4', 'NISN');
        $sheet->setCellValue('E4', 'NAMA SISWA');
        $sheet->setCellValue('F4', 'KELAS');
        $sheet->setCellValue('G4', 'TIPE');
        $sheet->setCellValue('H4', 'STATUS / KATEGORI');
        $sheet->setCellValue('I4', 'TERLAMBAT (MENIT)');
        $sheet->setCellValue('J4', 'METODE');
        $sheet->setCellValue('K4', 'KETERANGAN');

        $sheet->getStyle('A4:K4')->getFont()->setBold(true);
        $sheet->getStyle('A4:K4')->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF0F172A');
        $sheet->getStyle('A4:K4')->getFont()->getColor()->setARGB('FFFFFFFF');

        $row = 5;
        foreach ($logs as $index => $log) {
            $sheet->setCellValue("A{$row}", $index + 1);
            $sheet->setCellValue("B{$row}", $log->tanggal->format('Y-m-d'));
            $sheet->setCellValue("C{$row}", substr($log->waktu, 0, 5));
            $sheet->setCellValueExplicit("D{$row}", $log->student?->nisn ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValue("E{$row}", $log->student?->nama ?? '-');
            $sheet->setCellValue("F{$row}", $log->student?->kelas ?? '-');
            $sheet->setCellValue("G{$row}", ucfirst($log->tipe));
            $sheet->setCellValue("H{$row}", $log->category?->nama ?? '-');
            $sheet->setCellValue("I{$row}", $log->menit_terlambat > 0 ? $log->menit_terlambat : 0);
            $sheet->setCellValue("J{$row}", strtoupper($log->metode));
            $sheet->setCellValue("K{$row}", $log->keterangan ?? '-');
            $row++;
        }

        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'laporan_absensi_siswa_'.$dateFrom.'_'.$dateTo.'.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
