<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class lhe_2023 extends Model
{
    use HasFactory;
    protected $table = 'lhe_2023';
    
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
