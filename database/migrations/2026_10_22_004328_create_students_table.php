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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('nisn', 20)->unique();
            $table->string('nama', 150);
            $table->string('kelas', 30)->index();
            $table->string('rfid_uid', 50)->nullable()->unique()->index();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->string('status', 20)->default('aktif')->index();
            $table->string('no_telepon', 30)->nullable();
            $table->string('foto', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
