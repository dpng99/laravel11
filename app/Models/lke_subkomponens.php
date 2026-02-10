<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class lke_subkomponens extends Model
{
    use HasFactory;
    protected $table = 'lke_subkomponen';
    
    public $timestamps = false; // kalau tabelmu tidak punya created_at & updated_at
    protected $fillable = [
        'id',
        'komponen_id',
        'kode',
        'nama',
        'created_at',
        'updated_at',
    ];
    public function kriterias()
    {
        // This tells Laravel: "This subkomponen has many kriterias"
        // It links the 'kode' column here to 'subkomponen_id' in the kriteria table
        return $this->hasMany(lke_kriteria::class, 'subkomponen_id', 'kode');
    }

    // --- ADD THIS FUNCTION (Optional but recommended) ---
    public function komponen()
    {
        return $this->belongsTo(lke_komponen::class, 'komponen_id', 'id');
    }
}
