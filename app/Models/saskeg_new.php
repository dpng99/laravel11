<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class saskeg_new extends Model
{
    protected $table = 'sakip_saskeg_new';
    protected $primaryKey = 'kode_saskeg';

    // PENTING: Matikan auto increment
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_sastra',
        'id_saspro',
        'kode_saskeg',
        'nama_saskeg',
        'deskripsi',
    ];
     
    // Relasi ke Induk (Saspro)
    public function saspro()
    {
        return $this->belongsTo(saspro_new::class, 'id_saspro', 'kode_saspro');
    }

    // Relasi ke Induk (Sastra) - Optional karena sudah ada di Saspro
    public function sastra()
    {
        return $this->belongsTo(sastra_new::class, 'id_sastra', 'kode_sastra');
    }

    // Relasi ke Anak (Indikator)
    public function indikator()
    {
        return $this->hasMany(saspro_indikator_new::class, 'id_saskeg', 'kode_saskeg');
    }
}