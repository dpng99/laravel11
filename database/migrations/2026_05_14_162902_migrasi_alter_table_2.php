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
        // Daftar tabel berdasarkan analisis file SQL Anda
        $documentTables = [
            'sinori_sakip_pk', 'sinori_sakip_lkjip', 'sinori_sakip_dipa', 
            'sinori_sakip_renstra', 'sinori_sakip_renja', 'sinori_sakip_iku', 
            'sinori_sakip_rkakl', 'sinori_sakip_renaskieval', 'sinori_sakip_renaksi', 
            'sinori_sakip_rastaff', 'sinori_sakip_lhe', 'sinori_sakip_lakip', 
            'sinori_sakip_keputusan', 'tl_lhe_akip', 'target', 'tar_pm', 
            'tar_lkjip', 'pk', 'pengukuran', 'bukti_dukung'
        ];

        foreach ($documentTables as $tableName) {
            // Pastikan tabel ada dan memiliki kolom id_satker sebelum memasang FK
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'id_satker')) {
                Schema::table($tableName, function (Blueprint $table) {
                    // Mencegah error jika FK sudah pernah dibuat sebelumnya
                    $table->foreign('id_satker')
                          ->references('id_satker')
                          ->on('sinori_login')
                          ->onDelete('cascade')
                          ->onUpdate('cascade');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
  public function down(): void
    {
        $documentTables = [
            'sinori_sakip_pk', 'sinori_sakip_lkjip', 'sinori_sakip_dipa', 
            'sinori_sakip_renstra', 'sinori_sakip_renja', 'sinori_sakip_iku', 
            'sinori_sakip_rkakl', 'sinori_sakip_renaskieval', 'sinori_sakip_renaksi', 
            'sinori_sakip_rastaff', 'sinori_sakip_lhe', 'sinori_sakip_lakip', 
            'sinori_sakip_keputusan', 'tl_lhe_akip', 'target', 'tar_pm', 
            'tar_lkjip', 'pk', 'pengukuran', 'bukti_dukung'
        ];

        foreach ($documentTables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'id_satker')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    // Gunakan array untuk menghapus FK berdasarkan nama kolom
                    $table->dropForeign([ 'id_satker' ]);
                });
            }
        }
    }
};