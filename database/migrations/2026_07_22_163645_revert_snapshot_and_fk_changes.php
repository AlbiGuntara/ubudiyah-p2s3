<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pelanggaran', function (Blueprint $table) {
            $table->dropColumn(['santri_nama', 'santri_nis', 'santri_iksass', 'asrama_info']);
        });

        Schema::table('pembinaan', function (Blueprint $table) {
            $table->dropColumn(['santri_nama', 'santri_nis', 'santri_iksass', 'asrama_info']);
        });

        Schema::table('pelanggaran', function (Blueprint $table) {
            $table->dropForeign(['santri_id']);
            $table->foreign('santri_id')->references('id')->on('santri')->cascadeOnDelete();
        });

        Schema::table('pembinaan', function (Blueprint $table) {
            $table->dropForeign(['santri_id']);
            $table->foreign('santri_id')->references('id')->on('santri')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pembinaan', function (Blueprint $table) {
            $table->string('santri_nama')->nullable()->after('santri_id');
            $table->string('santri_nis')->nullable()->after('santri_nama');
            $table->string('santri_iksass')->nullable()->after('santri_nis');
            $table->string('asrama_info')->nullable()->after('asrama_id');
        });

        Schema::table('pelanggaran', function (Blueprint $table) {
            $table->string('santri_nama')->nullable()->after('santri_id');
            $table->string('santri_nis')->nullable()->after('santri_nama');
            $table->string('santri_iksass')->nullable()->after('santri_nis');
            $table->string('asrama_info')->nullable()->after('asrama_id');
        });

        Schema::table('pelanggaran', function (Blueprint $table) {
            $table->dropForeign(['santri_id']);
            $table->foreign('santri_id')->references('id')->on('santri')->nullOnDelete();
        });

        Schema::table('pembinaan', function (Blueprint $table) {
            $table->dropForeign(['santri_id']);
            $table->foreign('santri_id')->references('id')->on('santri')->nullOnDelete();
        });
    }
};
