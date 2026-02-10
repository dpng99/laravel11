<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SinoriSakipPidum extends Model
{
    use HasFactory;
    protected $table = 'sinori_sakip_pidum';

    protected $fillable = [
        'id_indikator',
        'id_satker',
        'target_indikator',
        'indikator',
        'id_tahun',
        'tw1',
        'tw2',
        'tw3',
        'tw4',
    ];

    // Nonaktifkan timestamps
    public $timestamps = false;

    public static function getData($idSatker, $tahun, $indicatorIds = [])
{
    $query = self::where('id_satker', $idSatker)
                 ->where('id_tahun', $tahun);
                 $query->whereIn('id_indikator', $indicatorIds);
    return $query->get()->keyBy('id_indikator');
}


    // Anda dapat menambahkan relasi jika perlu
    // Misalnya, jika ada relasi dengan model lain, Anda bisa mendefinisikannya di sini
}
