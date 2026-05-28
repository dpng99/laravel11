<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class sastra_indikator_view extends Model
{
    protected $table = 'sastra_indikator_view';

    protected $primaryKey = 'kode_indikator';
    

    public $timestamps = false;

    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            return false; // Membatalkan proses insert / update
        });

        static::deleting(function ($model) {
            return false; // Membatalkan proses delete
        });
    }
}
