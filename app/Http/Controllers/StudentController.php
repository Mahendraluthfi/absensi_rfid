<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    /**
     * Display a listing of students.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $kelas = $request->query('kelas', 'Semua');
        $rfidStatus = $request->query('rfid_status', 'all');

        $query = Student::query()
            ->search($search)
            ->byClass($kelas);

        if ($rfidStatus === 'yes') {
            $query->whereNotNull('rfid_uid');
        } elseif ($rfidStatus === 'no') {
            $query->whereNull('rfid_uid');
        }

        $students = $query->orderBy('kelas')->orderBy('nama')->paginate(15)->withQueryString();

        $classes = SchoolClass::active()->ordered()->pluck('nama');
        if ($classes->isEmpty()) {
            $classes = Student::select('kelas')->distinct()->orderBy('kelas')->pluck('kelas');
        }

        $stats = [
            'total' => Student::count(),
            'active' => Student::where('status', 'aktif')->count(),
            'with_rfid' => Student::whereNotNull('rfid_uid')->count(),
            'without_rfid' => Student::whereNull('rfid_uid')->count(),
        ];

        return Inertia::render('Students/Index', [
            'students' => $students,
            'filters' => [
                'search' => $search,
                'kelas' => $kelas,
                'rfid_status' => $rfidStatus,
            ],
            'classes' => $classes,
            'stats' => $stats,
        ]);
    }

    /**
     * Store a newly created student.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nisn' => ['required', 'string', 'max:20', 'unique:students,nisn'],
            'nama' => ['required', 'string', 'max:150'],
            'kelas' => ['required', 'string', 'max:30'],
            'rfid_uid' => ['nullable', 'string', 'max:50', 'unique:students,rfid_uid'],
            'jenis_kelamin' => ['nullable', 'in:L,P'],
            'status' => ['required', 'in:aktif,nonaktif,lulus'],
            'no_telepon' => ['nullable', 'string', 'max:30'],
            'foto' => ['nullable'],
        ]);

        if ($request->hasFile('foto')) {
            $request->validate(['foto' => ['image', 'mimes:jpeg,png,jpg,svg,webp', 'max:4096']]);
            $file = $request->file('foto');
            $fileName = $validated['nisn'].'_'.time().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('photos'), $fileName);
            $validated['foto'] = '/photos/'.$fileName;
        }

        $validated['rfid_uid'] = ! empty($validated['rfid_uid']) ? $validated['rfid_uid'] : null;
        $validated['no_telepon'] = ! empty($validated['no_telepon']) ? $validated['no_telepon'] : null;
        $validated['jenis_kelamin'] = ! empty($validated['jenis_kelamin']) ? $validated['jenis_kelamin'] : null;

        $schoolClass = SchoolClass::where('nama', $validated['kelas'])->first();
        $validated['school_class_id'] = $schoolClass?->id;

        Student::create($validated);

        return redirect()->back()->with('success', 'Data siswa berhasil ditambahkan.');
    }

    /**
     * Update the specified student.
     */
    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'nisn' => ['required', 'string', 'max:20', Rule::unique('students')->ignore($student->id)],
            'nama' => ['required', 'string', 'max:150'],
            'kelas' => ['required', 'string', 'max:30'],
            'rfid_uid' => ['nullable', 'string', 'max:50', Rule::unique('students')->ignore($student->id)],
            'jenis_kelamin' => ['nullable', 'in:L,P'],
            'status' => ['required', 'in:aktif,nonaktif,lulus'],
            'no_telepon' => ['nullable', 'string', 'max:30'],
            'foto' => ['nullable'],
        ]);

        if ($request->hasFile('foto')) {
            $request->validate(['foto' => ['image', 'mimes:jpeg,png,jpg,svg,webp', 'max:4096']]);
            $file = $request->file('foto');
            $fileName = $validated['nisn'].'_'.time().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('photos'), $fileName);
            $validated['foto'] = '/photos/'.$fileName;
        } else {
            unset($validated['foto']);
        }

        $validated['rfid_uid'] = ! empty($validated['rfid_uid']) ? $validated['rfid_uid'] : null;
        $validated['no_telepon'] = ! empty($validated['no_telepon']) ? $validated['no_telepon'] : null;
        $validated['jenis_kelamin'] = ! empty($validated['jenis_kelamin']) ? $validated['jenis_kelamin'] : null;

        $schoolClass = SchoolClass::where('nama', $validated['kelas'])->first();
        $validated['school_class_id'] = $schoolClass?->id;

        $student->update($validated);

        return redirect()->back()->with('success', 'Data siswa berhasil diperbarui.');
    }

    /**
     * Remove the specified student.
     */
    public function destroy(Student $student): RedirectResponse
    {
        $student->delete();

        return redirect()->back()->with('success', 'Data siswa berhasil dihapus.');
    }

    /**
     * Quick pair RFID UID to a student.
     */
    public function pairRfid(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'rfid_uid' => ['required', 'string', 'max:50'],
        ]);

        $existing = Student::where('rfid_uid', $validated['rfid_uid'])
            ->where('id', '!=', $validated['student_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => "Kartu RFID ini sudah dipakai oleh siswa: {$existing->nama} (Kelas {$existing->kelas}).",
            ], 422);
        }

        $student = Student::findOrFail($validated['student_id']);
        $student->update(['rfid_uid' => $validated['rfid_uid']]);

        return response()->json([
            'success' => true,
            'message' => "Kartu RFID berhasil dipasangkan ke siswa {$student->nama}.",
            'student' => $student,
        ]);
    }

    /**
     * Unpair RFID UID from student.
     */
    public function unpairRfid(Student $student): RedirectResponse
    {
        $student->update(['rfid_uid' => null]);

        return redirect()->back()->with('success', "Kartu RFID siswa {$student->nama} berhasil dilepas.");
    }

    /**
     * Import students from Excel file.
     */
    public function importExcel(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();

        $imported = 0;
        $updated = 0;

        for ($row = 2; $row <= $highestRow; $row++) {
            $colA = trim((string) $worksheet->getCell("A{$row}")->getValue());
            $colB = trim((string) $worksheet->getCell("B{$row}")->getValue());
            $colC = trim((string) $worksheet->getCell("C{$row}")->getValue());
            $colD = trim((string) $worksheet->getCell("D{$row}")->getValue());
            $colE = trim((string) $worksheet->getCell("E{$row}")->getValue());

            // Handle header if row is header
            if (str_contains(strtoupper($colA), 'NO') || str_contains(strtoupper($colB), 'NISN')) {
                continue;
            }

            // Detect column layout:
            // Standard format: A: NO, B: NISN, C: NAMA, D: KELAS, E: RFID (optional)
            $nisn = $colB ?: $colA;
            $nama = $colC ?: $colB;
            $kelas = $colD ?: 'VII';
            $rfidUid = $colE ?: null;

            if (empty($nisn) || empty($nama) || ! is_numeric(substr($nisn, 0, 1))) {
                continue;
            }

            $student = Student::where('nisn', $nisn)->first();
            if ($student) {
                $student->update([
                    'nama' => $nama,
                    'kelas' => $kelas,
                    'rfid_uid' => $rfidUid ?: $student->rfid_uid,
                ]);
                $updated++;
            } else {
                Student::create([
                    'nisn' => $nisn,
                    'nama' => $nama,
                    'kelas' => $kelas,
                    'rfid_uid' => $rfidUid,
                    'status' => 'aktif',
                ]);
                $imported++;
            }
        }

        return redirect()->back()->with('success', "Import selesai: {$imported} siswa baru ditambahkan, {$updated} siswa diperbarui.");
    }

    /**
     * Export all students to Excel.
     */
    public function exportExcel(): StreamedResponse
    {
        $students = Student::orderBy('kelas')->orderBy('nama')->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Siswa');

        // Headers
        $sheet->setCellValue('A1', 'NO.');
        $sheet->setCellValue('B1', 'NISN');
        $sheet->setCellValue('C1', 'NAMA SISWA');
        $sheet->setCellValue('D1', 'KELAS');
        $sheet->setCellValue('E1', 'RFID TAG UID');
        $sheet->setCellValue('F1', 'JENIS KELAMIN');
        $sheet->setCellValue('G1', 'STATUS');
        $sheet->setCellValue('H1', 'NO. TELEPON');

        // Style Header
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A1:H1')->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE2E8F0');

        $row = 2;
        foreach ($students as $index => $s) {
            $sheet->setCellValue("A{$row}", $index + 1);
            $sheet->setCellValueExplicit("B{$row}", $s->nisn, DataType::TYPE_STRING);
            $sheet->setCellValue("C{$row}", $s->nama);
            $sheet->setCellValue("D{$row}", $s->kelas);
            $sheet->setCellValueExplicit("E{$row}", $s->rfid_uid ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValue("F{$row}", $s->jenis_kelamin ?? '-');
            $sheet->setCellValue("G{$row}", ucfirst($s->status));
            $sheet->setCellValueExplicit("H{$row}", $s->no_telepon ?? '-', DataType::TYPE_STRING);
            $row++;
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'data_siswa_absensi_'.date('Ymd_His').'.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
