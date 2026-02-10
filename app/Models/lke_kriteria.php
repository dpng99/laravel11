<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class lke_kriteria extends Model
{
    use HasFactory;
    protected $table = 'lke_kriteria';
  
 public function buktiDukungs()
    {
        // This links Kriteria to Bukti Dukung via the 'lke_gabungan' table
        return $this->belongsToMany(
            lke_buktidukung::class, 
            'lke_gabungan', 
            'kriteria_id', 
            'buktidukung_id', 
            'kode', 
            'id'
        );
    }
    
    public function subKomponen()
    {
        return $this->belongsTo(lke_subkomponens::class, 'subkomponen_id', 'kode');
    }


}
