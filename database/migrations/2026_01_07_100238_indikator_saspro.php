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
        Schema::create('indikator_saspro', function (Blueprint $table) {
            $table->string('kode_indikator', 50)->primary();
            $table->string('kode_sastra')->nullable();
            $table->string('kode_saspro')->nullable();
            $table->string('nama_indikator_saspro', 255);
            $table->text('deskripsi_indikator_saspro', 255)->nullable();
            $table->foreign('kode_sastra')->references('id_sastra')->on('sakip_sastra_new')->onDelete('cascade');
            $table->foreign('kode_saspro')->references('id_saspro')->on('sakip_saspro_new')->onDelete('cascade');
           
        });
        //

            }        /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indikator_saspro');
    }
};
