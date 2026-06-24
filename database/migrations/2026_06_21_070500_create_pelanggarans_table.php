<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelanggaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('santri_id')->nullable()->constrained('santri')->cascadeOnDelete();
            $table->foreignId('asrama_id')->constrained('asrama')->cascadeOnDelete();
            $table->foreignId('daftar_pelanggaran_id')->constrained('daftar_pelanggaran')->cascadeOnDelete();
            $table->foreignId('petugas_id')->constrained('petugas')->cascadeOnDelete();
            $table->integer('jumlah')->default(1);
            $table->enum('sumber_pencatatan', ['petugas', 'ketua_kamar'])->default('petugas');
            $table->date('tanggal');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelanggaran');
    }
};
