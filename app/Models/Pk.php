<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pk extends Model
{
    use HasFactory;

    protected $table = 'pk';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'id_periode',
        'id_satker',
        'id_perubahan',
        'id_filename',
        'id_tglupload',
    ];
}
