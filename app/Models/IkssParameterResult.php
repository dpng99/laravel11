<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IkssParameterResult extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'verified_at' => 'datetime',
            'calculated_at' => 'datetime',
        ];
    }

    public function parameter()
    {
        return $this->belongsTo(IkssParameter::class, 'parameter_id');
    }

    public function items()
    {
        return $this->hasMany(IkssParameterResultItem::class, 'parameter_result_id')->orderBy('sort_order');
    }
}

