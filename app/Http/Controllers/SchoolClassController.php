<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SchoolClassController extends Controller
{
    /**
     * Display a listing of classes and statistics.
     */
    public function index(): Response
    {
        $classes = SchoolClass::withCount(['students', 'activeStudents'])
            ->ordered()
            ->get();

        $stats = [
            'total_classes' => SchoolClass::count(),
            'total_active_students' => Student::where('status', 'aktif')->count(),
            'total_graduated_students' => Student::where('status', 'lulus')->count(),
        ];

        return Inertia::render('Classes/Index', [
            'classes' => $classes,
            'stats' => $stats,
        ]);
    }

    /**
     * Store a newly created class.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:50', 'unique:school_classes,nama'],
            'tingkat' => ['required', 'integer', 'min:1', 'max:13'],
            'urutan' => ['nullable', 'integer', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:150'],
            'is_active' => ['boolean'],
        ]);

        $validated['urutan'] = $validated['urutan'] ?? (SchoolClass::max('urutan') + 1);
        $validated['is_active'] = $validated['is_active'] ?? true;

        SchoolClass::create($validated);

        return redirect()->back()->with('success', "Kelas \"{$validated['nama']}\" berhasil ditambahkan.");
    }

    /**
     * Update the specified class.
     */
    public function update(Request $request, SchoolClass $schoolClass): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:50', Rule::unique('school_classes')->ignore($schoolClass->id)],
            'tingkat' => ['required', 'integer', 'min:1', 'max:13'],
            'urutan' => ['nullable', 'integer', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:150'],
            'is_active' => ['boolean'],
        ]);

        $oldName = $schoolClass->nama;
        $schoolClass->update($validated);

        // If class name changed, update the string column 'kelas' in students table for consistency
        if ($oldName !== $validated['nama']) {
            Student::where('school_class_id', $schoolClass->id)
                ->update(['kelas' => $validated['nama']]);
        }

        return redirect()->back()->with('success', "Data kelas \"{$schoolClass->nama}\" berhasil diperbarui.");
    }

    /**
     * Remove the specified class.
     */
    public function destroy(SchoolClass $schoolClass): RedirectResponse
    {
        $studentCount = $schoolClass->students()->count();

        if ($studentCount > 0) {
            return redirect()->back()->with(
                'error',
                "Kelas \"{$schoolClass->nama}\" tidak dapat dihapus karena masih memiliki {$studentCount} siswa. Silakan pindahkan siswa terlebih dahulu melalui menu Kenaikan Kelas."
            );
        }

        $nama = $schoolClass->nama;
        $schoolClass->delete();

        return redirect()->back()->with('success', "Kelas \"{$nama}\" berhasil dihapus.");
    }

    /**
     * Get students belonging to a class (for promotion wizard).
     */
    public function getStudentsByClass(SchoolClass $schoolClass): JsonResponse
    {
        $students = $schoolClass->students()
            ->where('status', 'aktif')
            ->select('id', 'nisn', 'nama', 'kelas', 'rfid_uid', 'foto', 'status')
            ->orderBy('nama')
            ->get();

        return response()->json([
            'class' => $schoolClass,
            'students' => $students,
            'count' => $students->count(),
        ]);
    }

    /**
     * Handle mass student promotion or graduation.
     */
    public function promote(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:promote,graduate'],
            'source_class_id' => ['required', 'exists:school_classes,id'],
            'target_class_id' => [
                'required_if:action,promote',
                'nullable',
                'exists:school_classes,id',
                'different:source_class_id',
            ],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['exists:students,id'],
        ]);

        $sourceClass = SchoolClass::findOrFail($validated['source_class_id']);
        $studentCount = count($validated['student_ids']);

        DB::transaction(function () use ($validated) {
            if ($validated['action'] === 'promote') {
                $targetClass = SchoolClass::findOrFail($validated['target_class_id']);

                Student::whereIn('id', $validated['student_ids'])
                    ->update([
                        'school_class_id' => $targetClass->id,
                        'kelas' => $targetClass->nama,
                    ]);
            } elseif ($validated['action'] === 'graduate') {
                // Graduate students and unpair their RFID card so it can be recycled
                Student::whereIn('id', $validated['student_ids'])
                    ->update([
                        'status' => 'lulus',
                        'rfid_uid' => null,
                    ]);
            }
        });

        if ($validated['action'] === 'promote') {
            $targetClass = SchoolClass::find($validated['target_class_id']);

            return redirect()->back()->with(
                'success',
                "Berhasil memindahkan {$studentCount} siswa dari Kelas {$sourceClass->nama} ke Kelas {$targetClass->nama}."
            );
        }

        return redirect()->back()->with(
            'success',
            "Berhasil meluluskan {$studentCount} siswa dari Kelas {$sourceClass->nama}. Kartu RFID mereka telah otomatis di-unpair."
        );
    }
}
