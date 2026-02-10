<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SinoriSakipPidumDetail extends Model
{
    use HasFactory;

    protected $table = 'sinori_sakip_pidum_detail';

    protected $fillable = [
        'id_detail',
        'id', 
        'id_satker', 
        'matrix', 
        'bulan', 
        'ditangani', 
        'diselesaikan', 
        'faktor', 
        'upaya', 
        'created_at', 
        'updated_at'
    ];

    public $timestamps = false;
}
