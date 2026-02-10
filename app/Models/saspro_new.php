<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class saspro_new extends Model
{
    protected $table = 'sakip_saspro_new';
    protected $primaryKey = 'id_saspro';

    // PENTING: Matikan auto increment
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_sastra',
        'nama_saspro',
        'deskripsi',
    ];
    public $timestamps = false;
    // Relasi ke Induk (Sastra)
    public function sastra()
    {
        return $this->belongsTo(sastra_new::class, 'id_sastra', 'kode_sastra');
    }

    // Relasi ke Anak (Sasaran Kegiatan)
    public function saskeg()
    {
        return $this->hasMany(saskeg_new::class, 'id_saspro', 'kode_saspro');
    }

    // Relasi ke Indikator
    public function indikator()
    {
        return $this->hasMany(saspro_indikator_new::class, 'id_saspro', 'kode_saspro');
    }
}