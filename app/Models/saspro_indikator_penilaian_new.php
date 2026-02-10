<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class saspro_indikator_penilaian_new extends Model
{
    protected $table = 'saspro_indikator_penilaian_new';
    protected $primaryKey = 'kode_penilaian';

    // PENTING: Matikan auto increment
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_sastra',
        'id_saspro',
        'kode_indikator',
        'kode_penilaian',
        'nama_penilaian',
        'deskripsi',
    ];

    // Relasi ke Induk (Indikator)
    public function indikator()
    {
        return $this->belongsTo(saspro_indikator_new::class, 'kode_indikator', 'kode_indikator');
    }
}