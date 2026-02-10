<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Iku extends Model
{
    use HasFactory;
    protected $table = 'sinori_sakip_iku';
    public $timestamps = false;

    protected $fillable = [
        'id_filename',
        'id_periode',
        'id_perubahan',
        'id_tglupload',
        'id_satker',
    ];
    public static function getData($id_satker, $tahun)
    {
        return self::where('id_satker', $id_satker)
        ->where('id_periode', $tahun)
        ->get();
    }
}
