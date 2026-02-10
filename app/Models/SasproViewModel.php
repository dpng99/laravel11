<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SasproViewModel extends Model
{
   public $table = 'indikator_saspro_view'; 

    public $timestamps = false;
    public $incrementing = false;
    
    protected $primaryKey = 'kode_indikator';
    protected $keyType = 'string';
}
