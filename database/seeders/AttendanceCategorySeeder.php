<?php

namespace Database\Seeders;

use App\Models\AttendanceCategory;
use Illuminate\Database\Seeder;

class AttendanceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'kode' => 'ABSEN_PAGI',
                'nama' => 'Absen Berangkat (Pagi)',
                'tipe' => 'masuk',
                'jam_mulai' => '06:00:00',
                'jam_selesai' => '07:00:00',
                'toleransi_terlambat' => '08:30:00',
                'warna' => 'emerald',
                'is_active' => true,
            ],
            [
                'kode' => 'SHOLAT_DHUHUR',
                'nama' => 'Absen Sholat Dhuhur',
                'tipe' => 'ibadah',
                'jam_mulai' => '11:30:00',
                'jam_selesai' => '12:00:00',
                'toleransi_terlambat' => '13:00:00',
                'warna' => 'indigo',
                'is_active' => true,
            ],
            [
                'kode' => 'ABSEN_PULANG',
                'nama' => 'Absen Pulang Sekolah',
                'tipe' => 'pulang',
                'jam_mulai' => '14:00:00',
                'jam_selesai' => '15:00:00',
                'toleransi_terlambat' => '17:00:00',
                'warna' => 'blue',
                'is_active' => true,
            ],
            [
                'kode' => 'EKSKUL',
                'nama' => 'Absen Ekstrakurikuler / Sore',
                'tipe' => 'kegiatan',
                'jam_mulai' => '15:15:00',
                'jam_selesai' => '15:45:00',
                'toleransi_terlambat' => '17:30:00',
                'warna' => 'purple',
                'is_active' => true,
            ],
            [
                'kode' => 'SAKIT',
                'nama' => 'Sakit (Surat Dokter)',
                'tipe' => 'izin_sakit',
                'jam_mulai' => null,
                'jam_selesai' => null,
                'toleransi_terlambat' => null,
                'warna' => 'amber',
                'is_active' => true,
            ],
            [
                'kode' => 'IZIN',
                'nama' => 'Izin Resmi',
                'tipe' => 'izin_sakit',
                'jam_mulai' => null,
                'jam_selesai' => null,
                'toleransi_terlambat' => null,
                'warna' => 'rose',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $cat) {
            AttendanceCategory::updateOrCreate(['kode' => $cat['kode']], $cat);
        }
    }
}
