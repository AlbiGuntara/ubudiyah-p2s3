<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_panggilan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asrama_id')->constrained('asrama')->cascadeOnDelete();
            $table->string('kode_surat');
            $table->date('tanggal_cetak');
            $table->foreignId('dicetak_oleh')->constrained('users');
            $table->timestamps();
        });

        Schema::create('pelanggaran_surat_panggilan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_panggilan_id')->constrained('surat_panggilan')->cascadeOnDelete();
            $table->foreignId('pelanggaran_id')->constrained('pelanggaran')->cascadeOnDelete();
            $table->unique(['surat_panggilan_id', 'pelanggaran_id'], 'psp_surat_pelanggaran_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelanggaran_surat_panggilan');
        Schema::dropIfExists('surat_panggilan');
    }
};
