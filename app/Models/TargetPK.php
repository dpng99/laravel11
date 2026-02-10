<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TargetPK extends Model
{
    use HasFactory;

    protected $table = 'target'; // Ganti sesuai nama tabel di database

    protected $fillable = [
        'indikator_id',
        'id_satker',
        'tahun',
        'target_tahun',
        'target_triwulan_1',
        'target_triwulan_2',
        'target_triwulan_3',
        'target_triwulan_4',
    ];
}
