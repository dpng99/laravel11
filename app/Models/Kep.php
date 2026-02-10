<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kep extends Model
{
    use HasFactory;
    protected $table = 'sinori_sakip_keputusan';

    public $timestamps = false;

    protected $fillable = [
        'id_satker',
        'id_nomorsurat',
        'id_filesurat',
        'id_tglsurat',
        'id_tahun',
    ];
}
