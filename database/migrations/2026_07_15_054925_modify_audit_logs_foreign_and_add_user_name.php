<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE `audit_logs` DROP FOREIGN KEY IF EXISTS `audit_logs_user_id_foreign`');

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('user_name', 255)->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `audit_logs` DROP FOREIGN KEY IF EXISTS `audit_logs_user_id_foreign`');

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreignId('user_id')->change();
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->dropColumn('user_name');
        });
    }
};
