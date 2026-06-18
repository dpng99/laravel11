<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'sinori_login';

    protected $fillable = [
        'id_satker',
        'satkernama',
        'satkerpass',
        'id_kejati',
        'id_kejari',
        'id_sakip_level',
        'id_hidesatker',
    ];

    protected $hidden = [
        'satkerpass',
        'satkerkey',
        'satkerlogindate',
        'user_fail',
        'satkerpage',
        'token',
        'pejabat_kasatker',
        'pejabat_bin',
        'pejabat_intel',
        'pejabat_pidum',
        'pejabat_pidsus',
        'pejabat_datun',
        'pejabat_militer',
        'pejabat_pengawasan',
        'pejabat_aset',
    ];

    protected $primaryKey = 'id_satker';

    public $timestamps = false;
}
