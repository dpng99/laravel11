<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class saspro_indikator_new extends Model
{
    protected $table = 'indikator_saspro';
    protected $primaryKey = 'kode_indikator';

    // PENTING: Matikan auto increment
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'kode_indikator',
        'kode_sastra',
        'kode_saspro',
        'nama_indikator',
        'deskripsi_indikator_saspro',
        'link',
        'lingkup',
        'tahun',
        'hide',
        'urutan',
    ];

    public $timestamps = false;

    // Relasi ke Saskeg
    // Relasi ke Saspro
    public function saspro()
    {
        return $this->belongsTo(saspro_new::class, 'kode_saspro', 'id_saspro');
    }

    // Relasi ke Penilaian (Anak)
    public function penilaian()
    {
        return $this->hasMany(saspro_indikator_penilaian_new::class, 'kode_indikator', 'kode_indikator');
    }
}
