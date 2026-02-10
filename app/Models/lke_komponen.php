<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class lke_komponen extends Model
{
    use HasFactory;
    protected $table = 'lke_komponen';
    
    public $timestamps = false; // kalau tabelmu tidak punya created_at & updated_at
    protected $fillable = [
        'id',
        'no',
        'nama',
        
    ];

    public function subKomponens()
    {
        return $this->hasMany(lke_subkomponens::class, 'komponen_id', 'id');
    }
}
