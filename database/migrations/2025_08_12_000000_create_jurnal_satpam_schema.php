<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 0) (Optional) Create database if you run this on a fresh MySQL server.
        // Comment out if your .env already points to an existing DB.
        try {
            DB::statement("CREATE DATABASE IF NOT EXISTS `jurnal_satpam` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (\Throwable $e) {
            // ignore if not supported by current driver
        }

        // 1) satpams
        if (!Schema::hasTable('satpams')) {
            Schema::create('satpams', function (Blueprint $table) {
                $table->id();
                $table->string('username', 50);
                $table->string('password', 255);
                $table->string('foto', 255)->nullable();
                $table->string('nama', 100);
                $table->string('role', 50);
                $table->timestamps();
            });
        }

        // 2) lokasis
        if (!Schema::hasTable('lokasis')) {
            Schema::create('lokasis', function (Blueprint $table) {
                $table->id();
                $table->string('nama_lokasi', 100);
                $table->text('alamat_lokasi');
                $table->string('foto', 255)->nullable();
                $table->tinyInteger('is_active')->default(1); // tinyint(1)
                $table->timestamps();
            });
        }

        // 3) shifts
        if (!Schema::hasTable('shifts')) {
            Schema::create('shifts', function (Blueprint $table) {
                $table->id();
                // lokasi_id references lokasis.id
                $table->unsignedBigInteger('lokasi_id');
                $table->string('nama_shift', 50);
                $table->time('mulai_shift');
                $table->time('selesai_shift');
                $table->timestamps();

                $table->foreign('lokasi_id')
                    ->references('id')->on('lokasis')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        }

        // 4) jurnal_satpams
        if (!Schema::hasTable('jurnal_satpams')) {
            Schema::create('jurnal_satpams', function (Blueprint $table) {
                $table->id();
                $table->date('tanggal');              // date
                $table->unsignedBigInteger('lokasi_id');  // FK -> lokasis.id
                $table->unsignedBigInteger('shift_id');   // FK -> shifts.id
                $table->unsignedBigInteger('user_id');    // FK -> satpams.id

                $table->string('cuaca', 50);

                // booleans as tinyint(1)
                $table->tinyInteger('is_kejadian_temuan')->default(0);
                $table->text('kejadian_temuan')->nullable();

                $table->tinyInteger('is_lembur')->default(0);
                $table->text('lembur')->nullable();

                $table->tinyInteger('is_proyek_vendor')->default(0);
                $table->text('proyek_vendor')->nullable();

                $table->tinyInteger('is_paket_dokumen')->default(0);
                $table->text('paket_dokumen')->nullable();

                $table->tinyInteger('is_tamu_belum_keluar')->default(0);
                $table->text('tamu_belum_keluar')->nullable();

                // per screenshot: is_karyawan_dinas_keluar
                $table->tinyInteger('is_karyawan_dinas_keluar')->default(0);
                $table->text('karyawan_dinas_keluar')->nullable();

                // per screenshot: is_barang_keluar
                $table->tinyInteger('is_barang_keluar')->default(0);
                $table->text('barang_keluar')->nullable();

                $table->tinyInteger('is_kendaraan_dinas_keluar')->default(0);
                $table->text('kendaraan_dinas_keluar')->nullable();

                $table->tinyInteger('is_lampu_mati')->default(0);
                $table->text('lampu_mati')->nullable();

                $table->text('info_tambahan');

                $table->string('status', 255)->default('waiting');

                $table->timestamps();

                $table->foreign('lokasi_id')
                    ->references('id')->on('lokasis')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();

                $table->foreign('shift_id')
                    ->references('id')->on('shifts')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();

                $table->foreign('user_id')
                    ->references('id')->on('satpams')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        }

        // 5) uploads
        if (!Schema::hasTable('uploads')) {
            Schema::create('uploads', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('jurnal_id'); // FK -> jurnal_satpams.id
                $table->string('file_path', 255);
                $table->timestamps();

                $table->foreign('jurnal_id')
                    ->references('id')->on('jurnal_satpams')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
            });
        }

        // 6) jadwals
        if (!Schema::hasTable('jadwals')) {
            Schema::create('jadwals', function (Blueprint $table) {
                $table->id();
                $table->date('tanggal');
                $table->unsignedBigInteger('lokasi_id')->nullable();  // nullable as requested
                $table->string('shift_nama', 255)->nullable();
                $table->unsignedBigInteger('user_id');               // FK -> satpams.id
                $table->string('status', 255)->default('Off Duty');
                $table->timestamps();

                $table->foreign('lokasi_id')
                    ->references('id')->on('lokasis')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();

                $table->foreign('user_id')
                    ->references('id')->on('satpams')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
            });
        }

        // 7) recent_activities
        if (!Schema::hasTable('recent_activities')) {
            Schema::create('recent_activities', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id'); // FK -> satpams.id
                $table->string('description', 255)->nullable();
                $table->string('severity', 255)->nullable();
                $table->timestamps();

                $table->foreign('user_id')
                    ->references('id')->on('satpams')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
            });
        }
    }

    /**
     * Intentionally left empty to AVOID dropping any existing data.
     * If you ever need to undo, write a separate, explicit teardown migration.
     */
    public function down(): void
    {
        // Hapus tabel yang memiliki foreign key dependencies terlebih dahulu
        if (Schema::hasTable('recent_activities')) {
            Schema::dropIfExists('recent_activities');
        }

        if (Schema::hasTable('jadwals')) {
            Schema::dropIfExists('jadwals');
        }

        if (Schema::hasTable('uploads')) {
            Schema::dropIfExists('uploads');
        }

        if (Schema::hasTable('jurnal_satpams')) {
            Schema::dropIfExists('jurnal_satpams');
        }

        if (Schema::hasTable('shifts')) {
            Schema::dropIfExists('shifts');
        }

        if (Schema::hasTable('lokasis')) {
            Schema::dropIfExists('lokasis');
        }

        if (Schema::hasTable('satpams')) {
            Schema::dropIfExists('satpams');
        }
    }
};
