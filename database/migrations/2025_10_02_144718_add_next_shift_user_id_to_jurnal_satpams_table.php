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
            $table->unsignedBigInteger('next_shift_user_id')->nullable()->after('user_id');
            $table->foreign('next_shift_user_id')
                  ->references('id')  // Primary key di tabel satpams
                  ->on('satpams')     // Nama tabel satpams (ganti jika berbeda, misal 'users')
                  ->onDelete('set null')  // Jika Satpam dihapus, set ke NULL (relasi jadi null)
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_satpams', function (Blueprint $table) {
            $table->dropForeign(['next_shift_user_id']);
            $table->dropColumn('next_shift_user_id');
        });
    }
};
