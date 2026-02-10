<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Indikator extends Model
{
    use HasFactory;

    // Nama tabel
    protected $table = 'sinori_sakip_indikator';

    // Kolom yang dapat diisi
    protected $fillable = [
        'id_saspro',
        'link',
        'lingkup',
        'indikator_nama',
        'indikator_pembilang',
        'indikator_penyebut',
        'indikator_penjelasan',
        'sub_indikator',
        'indikator_penghitungan',
        'tahun',
        'tren',
    ];

    // Jika tidak ada timestamps
    public $timestamps = false;

    // Relasi berdasarkan id_bidang
    public function bidangById()
    {
        return $this->belongsTo(Bidang::class, 'link', 'id');
    }

    // Relasi berdasarkan link dan rumpun
    public function bidangByLink()
    {
        return $this->belongsTo(Bidang::class, 'link', 'rumpun');
    }
    public function saspro()
    {
        return $this->belongsTo(Saspro::class, 'id_saspro');
    }
}
