<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'school_classes';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nama',
        'tingkat',
        'urutan',
        'keterangan',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tingkat' => 'integer',
        'urutan' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship with students.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'school_class_id');
    }

    /**
     * Relationship with active students.
     */
    public function activeStudents(): HasMany
    {
        return $this->students()->where('status', 'aktif');
    }

    /**
     * Scope query to active classes.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to order by tingkat and urutan.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('tingkat')->orderBy('urutan')->orderBy('nama');
    }
}
