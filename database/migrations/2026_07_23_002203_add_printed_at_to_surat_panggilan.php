<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat_panggilan', function (Blueprint $table) {
            $table->dateTime('printed_at')->nullable()->after('tanggal_cetak');
        });

        DB::statement('UPDATE surat_panggilan SET printed_at = created_at');
    }

    public function down(): void
    {
        Schema::table('surat_panggilan', function (Blueprint $table) {
            $table->dropColumn('printed_at');
        });
    }
};
