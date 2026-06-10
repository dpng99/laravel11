<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IkssCalculationRun extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'stats' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
