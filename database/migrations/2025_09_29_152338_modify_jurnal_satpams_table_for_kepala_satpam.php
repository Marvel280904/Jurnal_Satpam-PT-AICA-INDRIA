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
            // 1. Ganti nama 'cuaca' menjadi 'laporan_kegiatan' dan ubah tipenya
            $table->text('laporan_kegiatan')->after('user_id')->nullable();

            // 2. Ubah 'info_tambahan' menjadi nullable
            $table->text('info_tambahan')->nullable()->change();

            // 3. Ubah kolom is_barang_keluar dan is_kendaraan_dinas_keluar menjadi nullable
            $table->tinyInteger('is_barang_keluar')->nullable()->change();
            $table->tinyInteger('is_kendaraan_dinas_keluar')->nullable()->change();
            
            // 4. Hapus kolom-kolom yang tidak diperlukan lagi
            $table->dropColumn([
                'cuaca',
                'is_paket_dokumen',
                'paket_dokumen',
                'is_tamu_belum_keluar',
                'tamu_belum_keluar',
                'is_karyawan_dinas_keluar', // Perhatikan nama kolom ini di database Anda
                'karyawan_dinas_keluar',
                'is_lampu_mati',
                'lampu_mati'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_satpams', function (Blueprint $table) {
            // Mengembalikan semua perubahan jika di-rollback (kebalikan dari method up)
            $table->dropColumn('laporan_kegiatan');
            $table->string('cuaca')->after('user_id');

            $table->text('info_tambahan')->nullable(false)->change();

            $table->tinyInteger('is_barang_keluar')->nullable(false)->change();
            $table->tinyInteger('is_kendaraan_dinas_keluar')->nullable(false)->change();

            $table->tinyInteger('is_paket_dokumen');
            $table->text('paket_dokumen')->nullable();
            $table->tinyInteger('is_tamu_belum_keluar');
            $table->text('tamu_belum_keluar')->nullable();
            $table->tinyInteger('is_karyawan_dinas_keluar');
            $table->text('karyawan_dinas_keluar')->nullable();
            $table->tinyInteger('is_lampu_mati');
            $table->text('lampu_mati')->nullable();
        });
    }
};