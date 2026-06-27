<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            ['table' => 'daerah', 'index' => 'daerah_kode_unique'],
            ['table' => 'santri', 'index' => 'santri_nis_unique'],
        ] as $item) {
            $exists = DB::select(
                'SHOW INDEX FROM ' . $item['table'] . ' WHERE Key_name = ?',
                [$item['index']]
            );
            if (!empty($exists)) {
                DB::statement('ALTER TABLE ' . $item['table'] . ' DROP INDEX ' . $item['index']);
            }
        }
    }

    public function down(): void
    {
        foreach ([
            ['table' => 'daerah', 'index' => 'daerah_kode_unique', 'col' => 'kode'],
            ['table' => 'santri', 'index' => 'santri_nis_unique', 'col' => 'nis'],
        ] as $item) {
            $exists = DB::select(
                'SHOW INDEX FROM ' . $item['table'] . ' WHERE Key_name = ?',
                [$item['index']]
            );
            if (empty($exists)) {
                DB::statement(
                    'CREATE UNIQUE INDEX ' . $item['index']
                    . ' ON ' . $item['table'] . '(' . $item['col'] . ')'
                );
            }
        }
    }
};
