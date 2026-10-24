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
        Schema::create('attendance_categories', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique()->index();
            $table->string('nama', 100);
            $table->string('tipe', 30)->default('masuk')->index();
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->time('toleransi_terlambat')->nullable();
            $table->string('warna', 30)->default('emerald');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_categories');
    }
};
