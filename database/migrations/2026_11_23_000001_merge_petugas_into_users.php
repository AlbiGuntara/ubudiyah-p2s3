<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('foto')->nullable()->after('email');
            $table->string('jabatan', 100)->nullable()->after('foto');
            $table->string('tugas', 100)->nullable()->after('jabatan');
        });

        Schema::table('pelanggaran', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('daftar_pelanggaran_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        // Pindahkan profil petugas yang punya akun pengguna, lalu arahkan
        // riwayat pelanggarannya ke akun tersebut supaya tidak ikut terhapus
        // saat tabel petugas dihapus.
        DB::table('petugas')->orderBy('id')->chunk(200, function ($rows) {
            foreach ($rows as $petugas) {
                if (! $petugas->user_id) {
                    continue;
                }

                DB::table('users')->where('id', $petugas->user_id)->update([
                    'jabatan' => $petugas->jabatan,
                    'tugas' => $petugas->tugas,
                ]);

                DB::table('pelanggaran')
                    ->where('petugas_id', $petugas->id)
                    ->update(['user_id' => $petugas->user_id]);
            }
        });

        Schema::table('pelanggaran', function (Blueprint $table) {
            $table->dropForeign(['petugas_id']);
            $table->dropColumn('petugas_id');
        });

        Schema::dropIfExists('petugas');

        DB::table('permissions')->whereIn('name', [
            'view_petugas',
            'create_petugas',
            'edit_petugas',
            'delete_petugas',
        ])->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::create('petugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nama', 100)->nullable();
            $table->foreignId('daerah_id')->nullable()->constrained('daerah')->nullOnDelete();
            $table->foreignId('asrama_id')->nullable()->constrained('asrama')->nullOnDelete();
            $table->string('jabatan', 100);
            $table->string('tugas', 100);
            $table->timestamps();
        });

        Schema::table('pelanggaran', function (Blueprint $table) {
            $table->foreignId('petugas_id')
                ->nullable()
                ->after('daftar_pelanggaran_id')
                ->constrained('petugas')
                ->nullOnDelete();
        });

        Schema::table('pelanggaran', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['foto', 'jabatan', 'tugas']);
        });
    }
};
