<?php

namespace Database\Seeders;

use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;

class StudentExcelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = database_path('data/Data Semua Siswa Absensi (1).xlsx');
        if (! file_exists($filePath)) {
            $filePath = base_path('Data Semua Siswa Absensi (1).xlsx');
        }

        if (! file_exists($filePath)) {
            $this->command?->warn("Excel file not found at: {$filePath}");

            return;
        }

        // Ensure public photos directory exists
        $photosDir = public_path('photos');
        if (! File::exists($photosDir)) {
            File::makeDirectory($photosDir, 0755, true);
        }

        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();

        $studentsCreated = 0;
        $mockRfidCounter = 1000000001;

        // Color palettes for student avatars
        $bgColors = ['#4F46E5', '#2563EB', '#0D9488', '#059669', '#D97706', '#DB2777', '#7C3AED', '#475569'];

        for ($row = 4; $row <= $highestRow; $row++) {
            $nisn = trim((string) $worksheet->getCell("B{$row}")->getValue());
            $nama = trim((string) $worksheet->getCell("C{$row}")->getValue());
            $kelas = trim((string) $worksheet->getCell("D{$row}")->getValue());

            if (empty($nisn) || empty($nama)) {
                continue;
            }

            // Assign mock RFID UID to first 15 students for instant testing
            $rfidUid = null;
            if ($studentsCreated < 15) {
                $rfidUid = (string) ($mockRfidCounter + $studentsCreated);
            }

            // Pick avatar background color
            $bgColor = $bgColors[$studentsCreated % count($bgColors)];
            $initials = $this->getInitials($nama);

            // Generate clean SVG student portrait badge
            $photoPath = "photos/{$nisn}.svg";
            $fullPhotoPath = public_path($photoPath);

            $svgContent = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="200" height="200">
  <defs>
    <linearGradient id="grad-{$nisn}" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$bgColor}" stop-opacity="0.9" />
      <stop offset="100%" stop-color="#0f172a" />
    </linearGradient>
  </defs>
  <!-- Background Circle -->
  <rect width="200" height="200" rx="40" fill="url(#grad-{$nisn})" />
  
  <!-- Subtle Inner Border -->
  <rect x="6" y="6" width="188" height="188" rx="34" fill="none" stroke="#ffffff" stroke-width="2" stroke-opacity="0.25" />

  <!-- Student Silhouette Illustration -->
  <!-- Head -->
  <circle cx="100" cy="75" r="32" fill="#f8fafc" />
  
  <!-- School Uniform / Shoulders -->
  <path d="M45,175 C45,130 70,120 100,120 C130,120 155,130 155,175 Z" fill="#ffffff" opacity="0.95" />
  
  <!-- School Collar / Tie -->
  <polygon points="100,130 90,120 110,120" fill="#475569" />
  <polygon points="96,128 104,128 102,155 98,155" fill="#dc2626" />

  <!-- Initials Badge -->
  <rect x="65" y="165" width="70" height="22" rx="11" fill="#0f172a" opacity="0.85" />
  <text x="100" y="180" font-family="Arial, sans-serif" font-size="11" font-weight="bold" fill="#38bdf8" text-anchor="middle">{$initials}</text>
</svg>
SVG;
            File::put($fullPhotoPath, $svgContent);

            Student::updateOrCreate(
                ['nisn' => $nisn],
                [
                    'nama' => $nama,
                    'kelas' => $kelas ?: 'VII',
                    'rfid_uid' => $rfidUid,
                    'foto' => "/{$photoPath}",
                    'status' => 'aktif',
                ]
            );

            $studentsCreated++;
        }

        $this->command?->info("Successfully seeded {$studentsCreated} students with photos from Excel.");
    }

    /**
     * Helper to get 2-letter initials from full name.
     */
    private function getInitials(string $name): string
    {
        $words = preg_split('/\s+/', trim($name));
        $initials = '';
        foreach ($words as $w) {
            if (! empty($w)) {
                $initials .= strtoupper($w[0]);
                if (strlen($initials) >= 2) {
                    break;
                }
            }
        }

        return $initials ?: 'SW';
    }
}
