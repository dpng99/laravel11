<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonevRenaksi extends Model
{
    use HasFactory;

    protected $table = 'sinori_sakip_renaksieval'; // Sesuai dengan tabel di database
    protected $primaryKey = 'id'; // Primary key tabel

    public $timestamps = false; // Jika tidak ada created_at dan updated_at
    protected $casts = [
        'id_tglupload' => 'string',
    ];
    
    protected $fillable = [
        'id_periode',
        'id_satker',
        'id_perubahan',
        'id_filename',
        'id_tglupload',
        'id_triwulan' // Tambahkan field ini
    ];
    
}
