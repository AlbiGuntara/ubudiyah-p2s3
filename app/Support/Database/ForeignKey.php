<?php

namespace App\Support\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pembantu untuk memeriksa dan menjatuhkan foreign key lintas driver.
 *
 * `information_schema` hanya ada di MySQL, sedangkan `ALTER TABLE ... DROP
 * FOREIGN KEY` tidak dipahami SQLite. Kedua hal itu membuat test suite
 * gagal karena `phpunit.xml` memakai SQLite in-memory. Semua pemeriksaan di
 * sini memakai Schema builder yang sudah diterjemahkan ke semua driver.
 */
final class ForeignKey
{
    /**
     * Apakah tabel punya foreign key pada kolom yang diberikan?
     *
     * @param  list<string>  $columns
     */
    public static function exists(string $table, array $columns): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        $wanted = array_map('strval', $columns);
        sort($wanted);

        foreach (Schema::getForeignKeys($table) as $foreign) {
            $actual = array_map('strval', (array) ($foreign['columns'] ?? []));
            sort($actual);

            if ($actual === $wanted) {
                return true;
            }
        }

        return false;
    }

    /**
     * Menjatuhkan foreign key bila ada, lalu menjalankan callback.
     *
     * Pemeriksaan dilakukan sebelum blueprint dibuat supaya driver yang
     * tidak mendukung `DROP FOREIGN KEY` tidak diberi perintah yang sia-sia.
     */
    /**
     * @param  list<string>  $columns
     */
    public static function dropIfExists(string $table, array $columns, ?callable $then = null): void
    {
        if (self::exists($table, $columns)) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                $blueprint->dropForeign($columns);
            });
        }

        if ($then !== null) {
            $then();
        }
    }
}
