<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menyesuaikan foreign key `audit_logs.user_id` dan menambah kolom
 * `user_name` sebagai salinan nama, supaya log tetap terbaca setelah user
 * dihapus.
 *
 * Versi lama menjatuhkan foreign key dengan `ALTER TABLE ... DROP FOREIGN
 * KEY IF EXISTS`, yaitu sintaks MySQL yang tidak dipahami SQLite, sehingga
 * seluruh test suite gagal. Pemeriksaan foreign key sekarang memakai
 * Schema builder yang sudah diterjemahkan ke semua driver.
 */
return new class extends Migration
{
    /**
     * Menjatuhkan foreign key `user_id` bila masih ada.
     */
    private function dropUserForeign(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        foreach (Schema::getForeignKeys('audit_logs') as $foreign) {
            $columns = (array) ($foreign['columns'] ?? []);

            if ($columns === ['user_id']) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->dropForeign(['user_id']);
                });
            }
        }
    }

    public function up(): void
    {
        $this->dropUserForeign();

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
        $this->dropUserForeign();

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreignId('user_id')->change();
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->dropColumn('user_name');
        });
    }
};
