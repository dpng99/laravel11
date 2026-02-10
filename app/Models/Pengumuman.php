<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengumuman extends Model
{
    use HasFactory;

    protected $table = 'sinori_sakip_inbox';
    public $timestamps = false;
    protected $fillable = [
        'judul',
        'isi',
        'tanggal',
        'tglpost',
    ];
}
