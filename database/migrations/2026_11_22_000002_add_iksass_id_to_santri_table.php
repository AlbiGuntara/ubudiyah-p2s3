<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('santri', 'iksass_id')) {
            Schema::table('santri', function (Blueprint $table) {
                $table->foreignId('iksass_id')
                    ->nullable()
                    ->after('iksass')
                    ->constrained('iksass')
                    ->nullOnDelete();
            });
        }

        // Turn every asal (IKSASS) value that already exists on santri into
        // master data, then point the santri rows at it.
        $namaIksass = DB::table('santri')
            ->whereNotNull('iksass')
            ->where('iksass', '!=', '')
            ->distinct()
            ->pluck('iksass');

        foreach ($namaIksass as $nama) {
            $id = DB::table('iksass')->insertGetId([
                'kode' => $this->kodeUnik((string) $nama),
                'nama_iksass' => $nama,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('santri')
                ->where('iksass', $nama)
                ->update(['iksass_id' => $id]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('santri', 'iksass_id')) {
            Schema::table('santri', function (Blueprint $table) {
                $table->dropConstrainedForeignId('iksass_id');
            });
        }
    }

    private function kodeUnik(string $nama): string
    {
        $base = substr(Str::upper(Str::slug($nama, '')), 0, 18);
        if ($base === '') {
            $base = 'IKS';
        }

        $kode = $base;
        $suffix = 2;
        while (DB::table('iksass')->where('kode', $kode)->exists()) {
            $kode = $base.'-'.$suffix;
            $suffix++;
        }

        return $kode;
    }
};
