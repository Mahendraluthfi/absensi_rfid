<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Database\Seeder;

class SchoolClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classes = [
            ['nama' => 'VII', 'tingkat' => 7, 'urutan' => 1, 'keterangan' => 'Kelas VII', 'is_active' => true],
            ['nama' => 'VIII A', 'tingkat' => 8, 'urutan' => 2, 'keterangan' => 'Kelas VIII A', 'is_active' => true],
            ['nama' => 'VIII B', 'tingkat' => 8, 'urutan' => 3, 'keterangan' => 'Kelas VIII B', 'is_active' => true],
            ['nama' => 'IX A', 'tingkat' => 9, 'urutan' => 4, 'keterangan' => 'Kelas IX A', 'is_active' => true],
            ['nama' => 'IX B', 'tingkat' => 9, 'urutan' => 5, 'keterangan' => 'Kelas IX B', 'is_active' => true],
            ['nama' => 'IX C', 'tingkat' => 9, 'urutan' => 6, 'keterangan' => 'Kelas IX C', 'is_active' => true],
        ];

        foreach ($classes as $cls) {
            $class = SchoolClass::firstOrCreate(
                ['nama' => $cls['nama']],
                [
                    'tingkat' => $cls['tingkat'],
                    'urutan' => $cls['urutan'],
                    'keterangan' => $cls['keterangan'],
                    'is_active' => $cls['is_active'],
                ]
            );

            // Sync with existing students
            Student::where('kelas', $cls['nama'])->update(['school_class_id' => $class->id]);
        }
    }
}
