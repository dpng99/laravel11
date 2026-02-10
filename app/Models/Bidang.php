<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bidang extends Model
{
    use HasFactory;

    protected $table = 'sinori_sakip_bidang';

    public $timestamps = false;
    /**
     * Kolom yang dapat diisi.
     */
    protected $fillable = [
        'bidang_nama',
        'bidang_level',
        'bidang_lokasi',
        'rumpun',
        'hide',
    ];

    public function indikator()
{
    return $this->hasMany(Indikator::class, 'link', 'rumpun');
}

}
