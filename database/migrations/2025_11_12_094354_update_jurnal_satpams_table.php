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
            $table->text('notes')->nullable();
            $table->boolean('is_read_notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_satpams', function (Blueprint $table) {
            $table->dropColumn('notes');
            $table->dropColumn('is_read_notes');
        });
    }
};
