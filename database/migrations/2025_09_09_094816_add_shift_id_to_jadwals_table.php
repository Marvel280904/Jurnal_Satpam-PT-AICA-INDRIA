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
        Schema::table('jadwals', function (Blueprint $table) {
            $table->unsignedBigInteger('shift_id')->nullable()->after('lokasi_id');
        });

        // Migrasi data lama: mapping shift_nama → shift.id
        $shifts = DB::table('shifts')->pluck('id', 'nama_shift'); // ['Shift Pagi' => 1, ...]
        foreach ($shifts as $nama => $id) {
            DB::table('jadwals')->where('shift_nama', $nama)->update(['shift_id' => $id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jadwals', function (Blueprint $table) {
            $table->dropColumn('shift_id');
        });
    }
};
