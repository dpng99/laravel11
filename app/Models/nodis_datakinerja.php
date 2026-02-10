<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class nodis_datakinerja extends Model
{
    use HasFactory;
    protected $table = 'nodis_datakinerja';
    
    public $timestamps = false; // kalau tabelmu tidak punya created_at & updated_at
    protected $fillable = [
        'no',
        'id_satker',
        'id_periode',
        'id_perubahan',
        'id_filename',
        'id_tglupload'
    ];
}
