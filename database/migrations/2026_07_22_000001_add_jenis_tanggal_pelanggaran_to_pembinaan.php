<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembinaan', function (Blueprint $table) {
            $table->string('jenis_pelanggaran')->nullable()->after('santri_id');
            $table->date('tanggal_pelanggaran')->nullable()->after('jenis_pelanggaran');
        });
    }

    public function down(): void
    {
        Schema::table('pembinaan', function (Blueprint $table) {
            $table->dropColumn(['jenis_pelanggaran', 'tanggal_pelanggaran']);
        });
    }
};
