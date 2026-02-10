<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class indikator_sastra_new extends Model
{
    protected $table = 'indikator_sastra';
    protected $primaryKey = 'kode_indikator';

    // Wajib dimatikan karena primary key Anda manual (String/Angka acak), bukan Auto Increment
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'kode_sastra',      // Foreign Key ke sakip_sastra_new// Primary Key
        'nama_indikator',
        'deskripsi'
    ];
    public $timestamps = false;
    /**
     * Relasi ke Tabel Induk (Sastra)
     * Menghubungkan id_sastra di tabel ini ke kode_sastra di tabel sastra
     */
    public function sastra()
    {
        return $this->belongsTo(sastra_new::class, 'id_sastra', 'kode_sastra');
    }
}