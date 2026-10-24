<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('school_settings', function (Blueprint $table) {
            $table->id();
            $table->string('nama_sekolah', 150)->default('Sistem Absensi RFID');
            $table->string('alamat', 255)->nullable();
            $table->time('jam_masuk_mulai')->default('06:00:00');
            $table->time('jam_masuk_batas')->default('07:00:00');
            $table->time('toleransi_terlambat_batas')->default('08:30:00');
            $table->time('jam_pulang_mulai')->default('14:00:00');
            $table->time('jam_pulang_batas')->default('17:00:00');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_settings');
    }
};
