<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataLke1 extends Model
{
    use HasFactory;

    protected $table = 'lke_kriteria';
    protected $fillable = [
        'id',
        'subkomponen_id',
        'kode',
        'nama',
        'dokumen_bukti',
    ];
    public $timestamps = false;
}
