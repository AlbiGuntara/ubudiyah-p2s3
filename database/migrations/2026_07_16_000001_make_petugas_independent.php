<?php

use App\Models\Petugas;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('petugas', function (Blueprint $table) {
            $table->string('nama', 100)->nullable()->after('id');
            $table->string('foto')->nullable()->after('nama');
            $table->foreignId('daerah_id')->nullable()->after('foto')->constrained('daerah')->nullOnDelete();
        });

        // Drop foreign key constraint first, then the column
        Schema::table('petugas', function (Blueprint $table) {
            $table->dropForeign(['santri_id']);
            $table->dropColumn('santri_id');
        });
    }

    public function down(): void
    {
        Schema::table('petugas', function (Blueprint $table) {
            $table->foreignId('santri_id')->nullable()->constrained('santri')->nullOnDelete();
        });

        Schema::table('petugas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('daerah_id');
            $table->dropColumn(['nama', 'foto']);
        });
    }
};
