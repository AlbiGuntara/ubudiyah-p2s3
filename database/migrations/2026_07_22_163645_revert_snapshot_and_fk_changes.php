<?php

use App\Support\Database\ForeignKey;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mengembalikan snapshot kolom di `pelanggaran` dan `pembinaan` serta
 * mengganti aturan penghapusan foreign key `santri_id`.
 *
 * Versi lama memeriksa foreign key lewat `information_schema`, yang hanya
 * ada di MySQL, sehingga test suite gagal saat memakai SQLite. Pemeriksaan
 * sekarang memakai `App\Support\Database\ForeignKey` yang lintas driver.
 */
return new class extends Migration
{
    private const SNAPSHOT_COLUMNS = ['santri_nama', 'santri_nis', 'santri_iksass', 'asrama_info'];

    /**
     * @return list<string>
     */
    private function tables(): array
    {
        return ['pelanggaran', 'pembinaan'];
    }

    public function up(): void
    {
        foreach ($this->tables() as $tableName) {
            $existing = array_values(array_filter(
                self::SNAPSHOT_COLUMNS,
                fn (string $column) => Schema::hasColumn($tableName, $column)
            ));

            if ($existing !== []) {
                Schema::table($tableName, function (Blueprint $table) use ($existing) {
                    $table->dropColumn($existing);
                });
            }
        }

        foreach ($this->tables() as $tableName) {
            ForeignKey::dropIfExists($tableName, ['santri_id']);

            Schema::table($tableName, function (Blueprint $table) {
                $table->foreign('santri_id')->references('id')->on('santri')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables() as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'santri_nama')) {
                    $table->string('santri_nama')->nullable()->after('santri_id');
                }
                if (! Schema::hasColumn($tableName, 'santri_nis')) {
                    $table->string('santri_nis')->nullable()->after('santri_nama');
                }
                if (! Schema::hasColumn($tableName, 'santri_iksass')) {
                    $table->string('santri_iksass')->nullable()->after('santri_nis');
                }
                if (! Schema::hasColumn($tableName, 'asrama_info')) {
                    $table->string('asrama_info')->nullable()->after('asrama_id');
                }
            });
        }

        foreach ($this->tables() as $tableName) {
            ForeignKey::dropIfExists($tableName, ['santri_id']);

            Schema::table($tableName, function (Blueprint $table) {
                $table->foreign('santri_id')->references('id')->on('santri')->nullOnDelete();
            });
        }
    }
};
