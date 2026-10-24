<?php

use App\Http\Controllers\AttendanceCategoryController;
use App\Http\Controllers\AttendanceReportController;
use App\Http\Controllers\AttendanceScanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

// Redirect root: if logged in to dashboard, else kiosk scan terminal
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('attendance.scan');
});

// Real-time RFID Scanner Terminal (Accessible for kiosk attendance station)
Route::get('/attendance/scan', [AttendanceScanController::class, 'index'])->name('attendance.scan');
Route::post('/attendance/scan', [AttendanceScanController::class, 'scan'])->name('attendance.scan.post');

// Authenticated Admin Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Master Data Siswa
    Route::get('/students', [StudentController::class, 'index'])->name('students.index');
    Route::post('/students', [StudentController::class, 'store'])->name('students.store');
    Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
    Route::post('/students/pair-rfid', [StudentController::class, 'pairRfid'])->name('students.pair-rfid');
    Route::post('/students/{student}/unpair-rfid', [StudentController::class, 'unpairRfid'])->name('students.unpair-rfid');
    Route::post('/students/import', [StudentController::class, 'importExcel'])->name('students.import');
    Route::get('/students/export', [StudentController::class, 'exportExcel'])->name('students.export');

    // Master Kategori Absensi & Pengaturan
    Route::get('/categories', [AttendanceCategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [AttendanceCategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [AttendanceCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [AttendanceCategoryController::class, 'destroy'])->name('categories.destroy');
    Route::post('/categories/{category}/toggle', [AttendanceCategoryController::class, 'toggleActive'])->name('categories.toggle');
    Route::post('/settings/school', [AttendanceCategoryController::class, 'updateSchoolSettings'])->name('settings.school');

    // Manual Attendance
    Route::post('/attendance/manual', [AttendanceScanController::class, 'manual'])->name('attendance.manual');

    // Laporan Absensi Siswa
    Route::get('/reports', [AttendanceReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [AttendanceReportController::class, 'exportExcel'])->name('reports.export');

    // Profile Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
