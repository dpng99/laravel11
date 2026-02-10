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
        Schema::create('indikator_sastra', function (Blueprint $table) {
            $table->string('kode_indikator')->primary();
            $table->string('kode_sastra');
            $table->string('nama_indikator_sastra');
            $table->text('deskripsi_indikator_sastra')->nullable();
            $table->foreign('kode_sastra')->references('id_sastra')->on('sakip_sastra_new')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
