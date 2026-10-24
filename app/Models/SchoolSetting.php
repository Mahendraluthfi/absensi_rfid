<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nama_sekolah',
        'alamat',
        'jam_masuk_mulai',
        'jam_masuk_batas',
        'toleransi_terlambat_batas',
        'jam_pulang_mulai',
        'jam_pulang_batas',
    ];

    /**
     * Get or create active settings singleton.
     */
    public static function current(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'nama_sekolah' => 'SMP Negeri Contoh',
                'alamat' => 'Jl. Pendidikan No. 123',
                'jam_masuk_mulai' => '06:00:00',
                'jam_masuk_batas' => '07:00:00',
                'toleransi_terlambat_batas' => '08:30:00',
                'jam_pulang_mulai' => '14:00:00',
                'jam_pulang_batas' => '17:00:00',
            ]
        );
    }
}
