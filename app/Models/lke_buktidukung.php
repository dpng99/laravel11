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
    ];
    
}
