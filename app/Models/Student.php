<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nisn',
        'nama',
        'kelas',
        'rfid_uid',
        'jenis_kelamin',
        'status',
        'no_telepon',
        'foto',
    ];

    /**
     * Relationship with attendance logs.
     */
    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    /**
     * Scope query to active students.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }

    /**
     * Scope query to filter by class.
     */
    public function scopeByClass(Builder $query, ?string $kelas = null): Builder
    {
        if ($kelas && $kelas !== 'Semua') {
            return $query->where('kelas', $kelas);
        }

        return $query;
    }

    /**
     * Scope query for search.
     */
    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('nama', 'like', "%{$search}%")
                ->orWhere('nisn', 'like', "%{$search}%")
                ->orWhere('rfid_uid', 'like', "%{$search}%");
        });
    }
}
