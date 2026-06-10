<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spip_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun')->default(2026);
            $table->string('user_id', 100);
            $table->string('name');
            $table->string('role', 50)->default('User');
            $table->string('allowed_satker', 100)->nullable();
            $table->string('password_pm_hash')->nullable();
            $table->string('password_pk_hash')->nullable();
            $table->string('status_pk', 50)->default('Tidak Aktif');
            $table->text('link_download')->nullable();
            $table->text('spreadsheet_url')->nullable();
            $table->string('gid', 80)->nullable();
            $table->text('edit_url')->nullable();
            $table->timestamps();

            $table->unique(['tahun', 'user_id']);
            $table->index(['tahun', 'role']);
            $table->index(['tahun', 'status_pk']);
        });

        Schema::create('spip_sub_unsurs', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun')->default(2026);
            $table->string('kode', 50)->nullable();
            $table->string('sub_unsur')->nullable();
            $table->unsignedInteger('nomor')->nullable();
            $table->string('kode_sub_unsur', 50);
            $table->text('uraian_parameter')->nullable();
            $table->string('spip', 50)->nullable();
            $table->string('mri', 50)->nullable();
            $table->string('iepk', 50)->nullable();
            $table->timestamps();

            $table->unique(['tahun', 'kode_sub_unsur']);
            $table->index(['tahun', 'kode']);
        });

        Schema::create('spip_kertas_kerjas', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun')->default(2026);
            $table->string('user_id', 100);
            $table->string('kode_sub_unsur', 50);
            $table->string('grade', 5);
            $table->text('kriteria')->nullable();
            $table->text('penjelasan')->nullable();
            $table->text('cara_pengujian')->nullable();
            $table->longText('uraian_hasil_pengujian')->nullable();
            $table->string('grade_pm', 5)->nullable();
            $table->string('grade_pk', 5)->nullable();
            $table->string('kluster_aoi')->nullable();
            $table->longText('uraian_aoi')->nullable();
            $table->string('kluster_penyebab')->nullable();
            $table->longText('uraian_penyebab')->nullable();
            $table->timestamps();

            $table->unique(['tahun', 'user_id', 'kode_sub_unsur', 'grade'], 'spip_kk_unique_grade');
            $table->index(['tahun', 'user_id']);
            $table->index(['tahun', 'kode_sub_unsur']);
        });

        Schema::create('spip_klusters', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun')->default(2026);
            $table->string('kluster_aoi')->nullable();
            $table->string('kluster_penyebab')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spip_klusters');
        Schema::dropIfExists('spip_kertas_kerjas');
        Schema::dropIfExists('spip_sub_unsurs');
        Schema::dropIfExists('spip_users');
    }
};
