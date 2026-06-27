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

        // Migrate existing data: set sisa_sanksi = sanksi - shalawat_tertulis
        DB::statement('UPDATE pembinaan SET sisa_sanksi = GREATEST(0, sanksi - shalawat_tertulis)');
    }

    public function down(): void
    {
        Schema::table('pembinaan', function (Blueprint $table) {
            $table->dropColumn('sisa_sanksi');
        });
    }
};
