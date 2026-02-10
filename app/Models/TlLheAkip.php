<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TlLheAkip extends Model
{
    use HasFactory;

    protected $table = 'tl_lhe_akip'; // Nama tabel di database

    protected $fillable = [
        'id_periode',
        'id_satker',
        'id_perubahan',
        'id_filename',
        'id_tglupload',
    ];

    public $timestamps = false;
}
