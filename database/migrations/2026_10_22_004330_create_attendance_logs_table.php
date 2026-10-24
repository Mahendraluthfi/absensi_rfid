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
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('attendance_categories')->onDelete('cascade');
            $table->string('rfid_uid', 50)->nullable();
            $table->date('tanggal')->index();
            $table->time('waktu');
            $table->string('tipe', 30)->default('masuk')->index();
            $table->string('metode', 20)->default('rfid');
            $table->integer('menit_terlambat')->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'tanggal', 'tipe']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
