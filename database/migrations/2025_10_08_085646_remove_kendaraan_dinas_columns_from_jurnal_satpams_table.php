<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('jurnal_satpams', function (Blueprint $table) {
            $table->dropColumn(['is_kendaraan_dinas_keluar', 'kendaraan_dinas_keluar']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_satpams', function (Blueprint $table) {
            $table->tinyInteger('is_kendaraan_dinas_keluar')->default(0)->after('is_barang_keluar');
            $table->text('kendaraan_dinas_keluar')->nullable()->after('is_kendaraan_dinas_keluar');
        });
    }
};
