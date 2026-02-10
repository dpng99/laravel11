<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LheAkip extends Model
{
    use HasFactory;
    protected $table = 'lhe';
    protected $fillable = [
        'id_satker',
        'id_periode',
        'id_perubahan',
        'id_filename',
        'id_tglupload',
    ];

    public $timestamps = false; // Nonaktifkan timestamps jika tidak menggunakan `created_at` dan `updated_at`
}
