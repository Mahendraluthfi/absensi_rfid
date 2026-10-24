<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCategory;
use App\Models\SchoolSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceCategoryController extends Controller
{
    /**
     * Display a listing of categories and school settings.
     */
    public function index(): Response
    {
        $categories = AttendanceCategory::withCount('attendanceLogs')
            ->orderBy('tipe')
            ->orderBy('id')
            ->get();

        $settings = SchoolSetting::current();

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
            'settings' => $settings,
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:30', 'unique:attendance_categories,kode'],
            'nama' => ['required', 'string', 'max:100'],
            'tipe' => ['required', 'string', 'max:50'],
            'jam_mulai' => ['nullable'],
            'jam_selesai' => ['nullable'],
            'toleransi_terlambat' => ['nullable'],
            'warna' => ['required', 'string', 'max:30'],
            'is_active' => ['boolean'],
        ]);

        $validated['kode'] = strtoupper($validated['kode']);
        $validated['jam_mulai'] = ! empty($validated['jam_mulai']) ? $validated['jam_mulai'] : null;
        $validated['jam_selesai'] = ! empty($validated['jam_selesai']) ? $validated['jam_selesai'] : null;
        $validated['toleransi_terlambat'] = ! empty($validated['toleransi_terlambat']) ? $validated['toleransi_terlambat'] : null;

        $validated['jam_mulai'] = !empty($validated['jam_mulai']) ? $validated['jam_mulai'] : null;
        $validated['jam_selesai'] = !empty($validated['jam_selesai']) ? $validated['jam_selesai'] : null;
        $validated['toleransi_terlambat'] = !empty($validated['toleransi_terlambat']) ? $validated['toleransi_terlambat'] : null;

        AttendanceCategory::create($validated);

        return redirect()->back()->with('success', 'Kategori absensi berhasil ditambahkan.');
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, AttendanceCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:30', Rule::unique('attendance_categories')->ignore($category->id)],
            'nama' => ['required', 'string', 'max:100'],
            'tipe' => ['required', 'string', 'max:50'],
            'jam_mulai' => ['nullable'],
            'jam_selesai' => ['nullable'],
            'toleransi_terlambat' => ['nullable'],
            'warna' => ['required', 'string', 'max:30'],
            'is_active' => ['boolean'],
        ]);

        $validated['kode'] = strtoupper($validated['kode']);
        $validated['jam_mulai'] = ! empty($validated['jam_mulai']) ? $validated['jam_mulai'] : null;
        $validated['jam_selesai'] = ! empty($validated['jam_selesai']) ? $validated['jam_selesai'] : null;
        $validated['toleransi_terlambat'] = ! empty($validated['toleransi_terlambat']) ? $validated['toleransi_terlambat'] : null;

        $validated['jam_mulai'] = !empty($validated['jam_mulai']) ? $validated['jam_mulai'] : null;
        $validated['jam_selesai'] = !empty($validated['jam_selesai']) ? $validated['jam_selesai'] : null;
        $validated['toleransi_terlambat'] = !empty($validated['toleransi_terlambat']) ? $validated['toleransi_terlambat'] : null;

        $category->update($validated);

        return redirect()->back()->with('success', 'Kategori absensi berhasil diperbarui.');
    }

    /**
     * Remove the specified category.
     */
    public function destroy(AttendanceCategory $category): RedirectResponse
    {
        if ($category->attendanceLogs()->count() > 0) {
            return redirect()->back()->with('error', 'Kategori tidak dapat dihapus karena sudah memiliki riwayat log absensi. Silakan nonaktifkan saja.');
        }

        $category->delete();

        return redirect()->back()->with('success', 'Kategori absensi berhasil dihapus.');
    }

    /**
     * Toggle active status.
     */
    public function toggleActive(AttendanceCategory $category): RedirectResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        return redirect()->back()->with('success', 'Status kategori berhasil diperbarui.');
    }

    /**
     * Update school settings.
     */
    public function updateSchoolSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_sekolah' => ['required', 'string', 'max:150'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'jam_masuk_mulai' => ['required'],
            'jam_masuk_batas' => ['required'],
            'toleransi_terlambat_batas' => ['required'],
            'jam_pulang_mulai' => ['required'],
            'jam_pulang_batas' => ['required'],
        ]);

        $settings = SchoolSetting::current();
        $settings->update($validated);

        return redirect()->back()->with('success', 'Pengaturan jam operasional sekolah berhasil disimpan.');
    }
}
