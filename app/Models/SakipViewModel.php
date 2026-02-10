<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SakipViewModel extends Model
{
    public $table = 'indikator_sastra_view'; 

    // View bersifat Read-Only
    public $timestamps = false;
    public $incrementing = false;
    
    // Definisikan Primary Key (asumsi kode_indikator unik)
    protected $primaryKey = 'kode_indikator';
    protected $keyType = 'string';
}
