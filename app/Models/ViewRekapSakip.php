<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ViewRekapSakip extends Model
{
   // Nama View di database
    public $table = 'view_rekap_sakip_lengkap'; 

    // View adalah Read-Only di mata Eloquent
    // Kita matikan timestamps karena view tidak punya kolom created_at/updated_at sendiri (hanya mengambil dari tabel lain)
    public $timestamps = false;
    
    // Matikan auto-increment karena primary key kita string (kode_penilaian)
    public $incrementing = false; 
    
    // Definisikan primary key agar fitur find() berfungsi
    protected $primaryKey = 'kode_penilaian';
    protected $keyType = 'string';

    // Karena ini View, kita tidak mendefinisikan $fillable 
    // (Tidak bisa insert/update langsung ke View ini)
}
