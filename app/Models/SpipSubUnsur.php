<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpipSubUnsur extends Model
{
    protected $fillable = [
        'tahun',
        'kode',
        'sub_unsur',
        'nomor',
        'kode_sub_unsur',
        'uraian_parameter',
        'spip',
        'mri',
        'iepk',
    ];
}
