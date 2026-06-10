<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpipKluster extends Model
{
    protected $fillable = [
        'tahun',
        'kluster_aoi',
        'kluster_penyebab',
    ];
}
