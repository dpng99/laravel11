<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpipUser extends Model
{
    protected $fillable = [
        'tahun',
        'user_id',
        'name',
        'role',
        'allowed_satker',
        'password_pm_hash',
        'password_pk_hash',
        'status_pk',
        'link_download',
        'spreadsheet_url',
        'gid',
        'edit_url',
    ];

    protected $hidden = [
        'password_pm_hash',
        'password_pk_hash',
    ];

    public function kertasKerja()
    {
        return $this->hasMany(SpipKertasKerja::class, 'user_id', 'user_id')
            ->where('tahun', $this->tahun);
    }
}
