<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menghapus unique index lama pada `daerah.kode` dan `santri.nis`.
 *
 * Versi sebelumnya memakai `SHOW INDEX`, yang hanya ada di MySQL, sehingga
 * seluruh test suite gagal saat memakai SQLite. Diganti Schema builder yang
 * sudah dip-porting ke semua driver.
 */
return new class extends Migration
{
    /**
     * @return list<array{table: string, index: string, column: string}>
     */
    private function targets(): array
    {
        return [
            ['table' => 'daerah', 'index' => 'daerah_kode_unique', 'column' => 'kode'],
            ['table' => 'santri', 'index' => 'santri_nis_unique', 'column' => 'nis'],
        ];
    }

    public function up(): void
    {
        foreach ($this->targets() as $item) {
            if (Schema::hasTable($item['table']) && Schema::hasIndex($item['table'], $item['index'])) {
                Schema::table($item['table'], function (Blueprint $table) use ($item) {
                    $table->dropUnique($item['index']);
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->targets() as $item) {
            if (Schema::hasTable($item['table']) && ! Schema::hasIndex($item['table'], $item['index'])) {
                Schema::table($item['table'], function (Blueprint $table) use ($item) {
                    $table->unique($item['column'], $item['index']);
                });
            }
        }
    }
};
