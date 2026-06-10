<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpipKertasKerja extends Model
{
    protected $fillable = [
        'tahun',
        'user_id',
        'kode_sub_unsur',
        'grade',
        'kriteria',
        'penjelasan',
        'cara_pengujian',
        'uraian_hasil_pengujian',
        'grade_pm',
        'grade_pk',
        'kluster_aoi',
        'uraian_aoi',
        'kluster_penyebab',
        'uraian_penyebab',
    ];
}
