<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pelaporan extends Model
{
    use HasFactory;

    protected $table = 'pengukuran'; // Pastikan sesuai dengan nama tabel di database

    protected $fillable = [
        'indikator_id',
        'id_satker',
        'tahun',
        'sub_indikator',
        'ditangani',
        'diselesaikan',
        'uraian_capaian',
        'faktor',
        'langkah_optimalisasi',
        'bulan',
        'sisa_tahun_lalu',
    ];
    public function indikator()
    {
        return $this->belongsTo(SinoriSakipIndikator::class, 'indikator_id');
    }
}
