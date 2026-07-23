<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = ['santri_nama', 'santri_nis', 'santri_iksass', 'asrama_info'];

        foreach (['pelanggaran', 'pembinaan'] as $tableName) {
            $existing = array_filter($columns, fn($c) => Schema::hasColumn($tableName, $c));
            if (!empty($existing)) {
                Schema::table($tableName, function (Blueprint $table) use ($existing) {
                    $table->dropColumn($existing);
                });
            }
        }

        foreach (['pelanggaran', 'pembinaan'] as $tableName) {
            $fkName = $tableName . '_santri_id_foreign';
            $fkExists = DB::select("
                SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ", [DB::getDatabaseName(), $tableName, $fkName]);

            Schema::table($tableName, function (Blueprint $table) use ($fkName, $fkExists) {
                if (!empty($fkExists)) {
                    $table->dropForeign($fkName);
                }
                $table->foreign('santri_id')->references('id')->on('santri')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['pembinaan', 'pelanggaran'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'santri_nama')) {
                    $table->string('santri_nama')->nullable()->after('santri_id');
                }
                if (!Schema::hasColumn($tableName, 'santri_nis')) {
                    $table->string('santri_nis')->nullable()->after('santri_nama');
                }
                if (!Schema::hasColumn($tableName, 'santri_iksass')) {
                    $table->string('santri_iksass')->nullable()->after('santri_nis');
                }
                if (!Schema::hasColumn($tableName, 'asrama_info')) {
                    $table->string('asrama_info')->nullable()->after('asrama_id');
                }
            });
        }

        foreach (['pelanggaran', 'pembinaan'] as $tableName) {
            $fkName = $tableName . '_santri_id_foreign';
            $fkExists = DB::select("
                SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ", [DB::getDatabaseName(), $tableName, $fkName]);

            Schema::table($tableName, function (Blueprint $table) use ($fkName, $fkExists) {
                if (!empty($fkExists)) {
                    $table->dropForeign($fkName);
                }
                $table->foreign('santri_id')->references('id')->on('santri')->nullOnDelete();
            });
        }
    }
};
