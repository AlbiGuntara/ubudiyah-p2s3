<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('santri', function (Blueprint $table) {
            $table->dropColumn('panggilan');
        });

        Schema::table('santri', function (Blueprint $table) {
            $table->string('nama_panggilan', 50)->nullable()->after('foto');
        });
    }

    public function down(): void
    {
        Schema::table('santri', function (Blueprint $table) {
            $table->dropColumn('nama_panggilan');
        });

        Schema::table('santri', function (Blueprint $table) {
            $table->enum('panggilan', ['I', 'II', 'III'])->nullable()->after('foto');
        });
    }
};
