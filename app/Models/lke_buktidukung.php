<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class lke_buktidukung extends Model
{
    use HasFactory;

    protected $table = 'lke_buktidukung';
    
    protected $fillable = [
        'id',
        'dokumen',
        'format_nama_file',
        'keterangan',
        'ada_di_sistem',
        'tabel_sumber',
        'tahun',
    ];

    protected function casts(): array
    {
        return [
            'ada_di_sistem' => 'boolean',
            'tahun' => 'integer',
        ];
    }
}
