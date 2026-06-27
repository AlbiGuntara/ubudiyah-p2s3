<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembinaan', function (Blueprint $table) {
            $table->foreignId('asrama_id')->nullable()->after('santri_id')->constrained('asrama')->nullOnDelete();
        });

        // Make santri_id nullable (drop FK constraint first, re-add)
        Schema::table('pembinaan', function (Blueprint $table) {
            $table->dropForeign(['santri_id']);
        });

        Schema::table('pembinaan', function (Blueprint $table) {
            $table->foreignId('santri_id')->nullable()->change();
        });

        Schema::table('pembinaan', function (Blueprint $table) {
            $table->foreign('santri_id')->references('id')->on('santri')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pembinaan', function (Blueprint $table) {
            $table->dropForeign(['asrama_id']);
            $table->dropColumn('asrama_id');
        });

        // Revert santri_id to not nullable
        Schema::table('pembinaan', function (Blueprint $table) {
            $table->dropForeign(['santri_id']);
        });

        // Set null values to some valid santri_id first (using a fallback)
        DB::table('pembinaan')->whereNull('santri_id')->update(['santri_id' => 1]);

        Schema::table('pembinaan', function (Blueprint $table) {
            $table->foreignId('santri_id')->nullable(false)->change();
        });

        Schema::table('pembinaan', function (Blueprint $table) {
            $table->foreign('santri_id')->references('id')->on('santri')->cascadeOnDelete();
        });
    }
};