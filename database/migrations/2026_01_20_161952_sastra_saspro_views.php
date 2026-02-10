<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //view indikator_sastra_view
        DB::statement("
        CREATE OR REPLACE VIEW indikator_sastra_view AS
        SELECT
            isv.kode_indikator,
            isv.nama_indikator,
            s.id_sastra,
            s.nama_sastra
        FROM indikator_sastra AS isv
        JOIN sakip_sastra_new AS s ON isv.kode_sastra = s.id_sastra
                ");

        //view indikator_saspro_view
        DB::statement("
        CREATE OR REPLACE VIEW indikator_saspro_view AS
        SELECT
            isp.kode_indikator,
            isp.nama_indikator,
            sp.id_saspro,
            sp.nama_saspro,
            s.id_sastra,
            s.nama_sastra
        FROM indikator_saspro AS isp
        JOIN sakip_saspro_new AS sp ON isp.kode_saspro = sp.id_saspro
        JOIN sakip_sastra_new AS s ON isp.kode_sastra = s.id_sastra
                ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS indikator_sastra_view");
        DB::statement("DROP VIEW IF EXISTS indikator_saspro_view");
    }
};
