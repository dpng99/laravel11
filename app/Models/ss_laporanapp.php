<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ss_laporanapp extends Model
{
    use HasFactory;
    protected $table = 'ss_laporapp';
    
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
