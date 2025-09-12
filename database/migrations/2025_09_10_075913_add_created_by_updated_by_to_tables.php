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
        // === Jadwals ===
        Schema::table('jadwals', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('updated_at');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');

            $table->foreign('created_by')->references('id')->on('satpams')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('satpams')->onDelete('set null');
        });

        // === Jurnal Satpams ===
        Schema::table('jurnal_satpams', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('updated_at');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');

            $table->foreign('created_by')->references('id')->on('satpams')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('satpams')->onDelete('set null');
        });

        // === Uploads ===
        Schema::table('uploads', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('updated_at');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');

            $table->foreign('created_by')->references('id')->on('satpams')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('satpams')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jadwals', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('jurnal_satpams', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('uploads', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['created_by', 'updated_by']);
        });
    }
};
