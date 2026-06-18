<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lke_gabungan', function (Blueprint $table) {
            $table->dropForeign('FK_lke_gabungan_lke_kriteria');
            $table->dropForeign('FK_lke_gabungan_lke_subkomponen');
        });
        Schema::table('lke_parameter', fn (Blueprint $table) => $table->dropForeign('lke_parameter_ibfk_1'));
        Schema::table('lke_kriteria', fn (Blueprint $table) => $table->dropForeign('FK_lke_kriteria_lke_subkomponen'));

        Schema::table('lke_subkomponen', function (Blueprint $table) {
            $table->dropPrimary();
            $table->primary(['tahun', 'kode'], 'lke_subkomponen_tahun_kode_primary');
        });

        Schema::table('lke_kriteria', function (Blueprint $table) {
            $table->dropPrimary();
            $table->primary(['tahun', 'kode'], 'lke_kriteria_tahun_kode_primary');
            $table->foreign(['tahun', 'subkomponen_id'], 'lke_kriteria_tahun_subkomponen_foreign')
                ->references(['tahun', 'kode'])
                ->on('lke_subkomponen')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
        Schema::table('lke_gabungan', function (Blueprint $table) {
            $table->foreign(['tahun', 'kriteria_id'], 'lke_gabungan_tahun_kriteria_foreign')
                ->references(['tahun', 'kode'])->on('lke_kriteria')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign(['tahun', 'sub_komponen_id'], 'lke_gabungan_tahun_subkomponen_foreign')
                ->references(['tahun', 'kode'])->on('lke_subkomponen')->cascadeOnUpdate()->cascadeOnDelete();
        });
        Schema::table('lke_parameter', function (Blueprint $table) {
            $table->foreign(['tahun', 'kriteria_id'], 'lke_parameter_tahun_kriteria_foreign')
                ->references(['tahun', 'kode'])->on('lke_kriteria')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lke_gabungan', function (Blueprint $table) {
            $table->dropForeign('lke_gabungan_tahun_kriteria_foreign');
            $table->dropForeign('lke_gabungan_tahun_subkomponen_foreign');
        });
        Schema::table('lke_parameter', fn (Blueprint $table) => $table->dropForeign('lke_parameter_tahun_kriteria_foreign'));
        Schema::table('lke_kriteria', function (Blueprint $table) {
            $table->dropForeign('lke_kriteria_tahun_subkomponen_foreign');
            $table->dropPrimary('lke_kriteria_tahun_kode_primary');
            $table->primary('kode');
        });

        Schema::table('lke_subkomponen', function (Blueprint $table) {
            $table->dropPrimary('lke_subkomponen_tahun_kode_primary');
            $table->primary('kode');
        });

        Schema::table('lke_kriteria', function (Blueprint $table) {
            $table->foreign('subkomponen_id', 'FK_lke_kriteria_lke_subkomponen')
                ->references('kode')
                ->on('lke_subkomponen')
                ->cascadeOnDelete();
        });
        Schema::table('lke_gabungan', function (Blueprint $table) {
            $table->foreign('kriteria_id', 'FK_lke_gabungan_lke_kriteria')->references('kode')->on('lke_kriteria')->cascadeOnDelete();
            $table->foreign('sub_komponen_id', 'FK_lke_gabungan_lke_subkomponen')->references('kode')->on('lke_subkomponen')->cascadeOnDelete();
        });
        Schema::table('lke_parameter', function (Blueprint $table) {
            $table->foreign('kriteria_id', 'lke_parameter_ibfk_1')->references('kode')->on('lke_kriteria')->cascadeOnUpdate()->restrictOnDelete();
        });
    }
};
