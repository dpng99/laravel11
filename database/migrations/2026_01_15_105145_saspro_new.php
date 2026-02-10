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
        Schema::create('sakip_saspro_new', function (Blueprint $table) {
            $table->string('id_saspro')->primary();
            $table->string('id_sastra');
            $table->string('nama_saspro');
            $table->text('deskripsi')->nullable();

            $table->foreign('id_sastra')->references('id_sastra')->on('sakip_sastra_new')->onDelete('cascade');
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
