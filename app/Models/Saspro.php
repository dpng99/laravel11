<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Saspro extends Model
{
    use HasFactory;

    protected $table = 'sinori_sakip_saspro';

    protected $fillable = [
        'link',
        'saspro_nama',
        'saspro_penjelasan',
        'lingkup',
        'tahun',
        'hide',
    ];
    // Jika tidak ada timestamps
    public $timestamps = false;

    public function bidang()
{
    return $this->belongsTo(Bidang::class, 'link', 'id');
}

}
