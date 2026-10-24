<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'student_id',
        'category_id',
        'rfid_uid',
        'tanggal',
        'waktu',
        'tipe',
        'metode',
        'menit_terlambat',
        'keterangan',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'menit_terlambat' => 'integer',
        ];
    }

    /**
     * Relationship with student.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relationship with category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(AttendanceCategory::class, 'category_id');
    }

    /**
     * Scope query to specific date.
     */
    public function scopeOnDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('tanggal', $date);
    }

    /**
     * Scope query to date range.
     */
    public function scopeBetweenDates(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('tanggal', [$startDate, $endDate]);
    }
}
