<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add sisa_sanksi column (dynamic, decreases when santri setor sanksi)
        Schema::table('pembinaan', function (Blueprint $table) {
            $table->integer('sisa_sanksi')->default(0)->after('shalawat_tertulis');
        });

        // Isi sisa_sanksi untuk data lama. MAX() dipakai, bukan GREATEST(),
        // karena GREATEST hanya tersedia di MySQL sedangkan suite test
        // memakai SQLite.
        DB::statement('UPDATE pembinaan SET sisa_sanksi = MAX(0, sanksi - shalawat_tertulis)');
    }

    public function down(): void
    {
        Schema::table('pembinaan', function (Blueprint $table) {
            $table->dropColumn('sisa_sanksi');
        });
    }
};
