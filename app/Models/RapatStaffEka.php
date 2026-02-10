<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RapatStaffEka extends Model
{
    use HasFactory;

    protected $table = 'sinori_sakip_rastaff'; // Nama tabel di database

    protected $fillable = [
        'id_periode',
        'id_satker',
        'id_perubahan',
        'id_filename',
        'id_tglupload',
        'id_triwulan',
    ];

    public $timestamps = false; // Karena kita menggunakan `id_tglupload` manual
}
