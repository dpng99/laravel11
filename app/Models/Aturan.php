<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aturan extends Model
{
    use HasFactory;

    protected $table = 'sinori_sakip_literasi'; // Nama tabel di database
    protected $fillable = [
        'id_namaproduk',
        'id_produsen',
        'id_tahun',
        'id_filename',
    ];
    // Menonaktifkan penggunaan kolom timestamps
    public $timestamps = false;
}
