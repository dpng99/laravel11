<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tar_lkjip extends Model
{
    use HasFactory;
    protected $table = 'tar_lkjip';
    
    public $timestamps = false; // kalau tabelmu tidak punya created_at & updated_at
    protected $fillable = [
        'no',
        'id_satker',
        'id_periode',
        'id_perubahan',
        'id_filename',
        'TW',
        'id_tglupload'
    ];
}
