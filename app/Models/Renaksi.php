<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Renaksi extends Model
{
    use HasFactory;
    protected $table = 'sinori_sakip_renaksi';
    public $timestamps = false;

    protected $fillable = [
        'id_filename',
        'id_periode',
        'id_perubahan',
        'id_tglupload',
        'id_satker',
        
    ];
    public static function getData($idSatker, $tahun)
    {
        return self::where('id_satker', $idSatker)
            ->where('id_periode', $tahun)
            ->get();
    }
}
