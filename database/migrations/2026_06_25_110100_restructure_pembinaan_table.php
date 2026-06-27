<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old columns
        Schema::table('pembinaan', function (Blueprint $table) {
            $table->dropColumn('panggilan');
            $table->dropColumn('tanggal_panggilan');
        });

        // Drop old sanksi text column and re-add as integer
        Schema::table('pembinaan', function (Blueprint $table) {
            $table->dropColumn('sanksi');
        });

        Schema::table('pembinaan', function (Blueprint $table) {
            $table->integer('sanksi')->default(0)->after('id');
            $table->integer('shalawat_tertulis')->default(0)->after('sanksi');
        });
    }

    public function down(): void
    {
        Schema::table('pembinaan', function (Blueprint $table) {
            $table->dropColumn('shalawat_tertulis');
            $table->dropColumn('sanksi');
        });

        Schema::table('pembinaan', function (Blueprint $table) {
            $table->text('sanksi')->nullable();
            $table->date('tanggal_panggilan');
            $table->enum('panggilan', ['I', 'II', 'III']);
        });
    }
};
