<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IkssParameterValue extends Model
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
        return $this->hasMany(IkssParameterValueItem::class, 'parameter_value_id')->orderBy('sort_order');
    }
}
