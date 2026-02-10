<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class indikator_saspro_new extends Model
{
    protected $table = 'saspro_indikator_new';

    /**
     * Kolom yang boleh diisi secara massal (Mass Assignment).
     */
    protected $primaryKey = 'kode_indikator';
    protected $fillable = [
        'kode_sastra',
        'kode_saspro',
        'nama_indikator',

        // 'deskripsi', // Tambahkan ini jika Anda berencana menambah kolom deskripsi nanti
    ];

    /**
     * Menonaktifkan timestamps jika tabel Anda tidak memiliki kolom created_at/updated_at.
     * Hapus baris ini jika Anda menggunakan timestamps (defaultnya true).
     */
    public $timestamps = true;

    /**
     * Relasi ke Saspro (Opsional)
     * Definisikan ini jika Anda ingin menghubungkan indikator ke sasaran program.
     */
    public function saspro()
    {
        // Perhatikan: Relasi standar Eloquent menggunakan satu kunci (Foreign Key).
        // Jika relasi Anda composite (kode_sastra + kode_saspro), Anda perlu penanganan khusus.
        return $this->belongsTo(saspro_new::class, 'kode_saspro', 'kode_saspro');
    }
}
