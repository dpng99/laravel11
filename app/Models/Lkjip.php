<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lkjip extends Model
{
    use HasFactory;

    protected $table = 'sinori_sakip_lakip';
    protected $fillable = [
        'id_periode',
        'id_satker',
        'id_perubahan',
        'id_filename',
        'id_tglupload',
        'id_triwulan',
    ];

    public $timestamps = false; // Nonaktifkan timestamps jika tidak menggunakan `created_at` dan `updated_at`
}
