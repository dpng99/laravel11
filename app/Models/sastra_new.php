<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class sastra_new extends Model
{
    protected $table = 'sakip_sastra_new';
    protected $primaryKey = 'id_sastra';
    
    // PENTING: Karena primary key bukan Integer Auto Increment
    public $incrementing = false; 
    protected $keyType = 'string';

    protected $fillable = [
        'id_sastra',
        'nama_sastra',
        'deskripsi',
    ];

    // Relasi ke Sasaran Program (1 Sastra punya banyak Saspro)
    public function saspro()
    {
        return $this->hasMany(saspro_new::class, 'id_sastra', 'kode_sastra');
    }

    // Relasi ke Indikator (Optional, jika ingin bypass saspro/saskeg)
    public function indikator()
    {
        return $this->hasMany(saspro_indikator_new::class, 'id_sastra', 'kode_sastra');
    }
}