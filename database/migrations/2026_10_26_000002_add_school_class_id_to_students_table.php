<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('school_class_id')
                ->nullable()
                ->after('nama')
                ->constrained('school_classes')
                ->nullOnDelete();
        });

        // Seed initial default classes from existing data
        $initialClasses = [
            ['nama' => 'VII', 'tingkat' => 7, 'urutan' => 1, 'keterangan' => 'Kelas VII', 'is_active' => true],
            ['nama' => 'VIII A', 'tingkat' => 8, 'urutan' => 2, 'keterangan' => 'Kelas VIII A', 'is_active' => true],
            ['nama' => 'VIII B', 'tingkat' => 8, 'urutan' => 3, 'keterangan' => 'Kelas VIII B', 'is_active' => true],
            ['nama' => 'IX A', 'tingkat' => 9, 'urutan' => 4, 'keterangan' => 'Kelas IX A', 'is_active' => true],
            ['nama' => 'IX B', 'tingkat' => 9, 'urutan' => 5, 'keterangan' => 'Kelas IX B', 'is_active' => true],
            ['nama' => 'IX C', 'tingkat' => 9, 'urutan' => 6, 'keterangan' => 'Kelas IX C', 'is_active' => true],
        ];

        foreach ($initialClasses as $cls) {
            $classId = DB::table('school_classes')->insertGetId([
                'nama' => $cls['nama'],
                'tingkat' => $cls['tingkat'],
                'urutan' => $cls['urutan'],
                'keterangan' => $cls['keterangan'],
                'is_active' => $cls['is_active'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Sync existing students with this class name
            DB::table('students')
                ->where('kelas', $cls['nama'])
                ->update(['school_class_id' => $classId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['school_class_id']);
            $table->dropColumn('school_class_id');
        });
    }
};

