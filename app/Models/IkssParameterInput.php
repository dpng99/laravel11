<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IkssParameterInput extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'verified_at' => 'datetime',
        ];
    }

    public function parameter()
    {
        return $this->belongsTo(IkssParameter::class, 'parameter_id');
    }

    public function items()
    {
        return $this->hasMany(IkssParameterInputItem::class, 'parameter_input_id')->orderBy('sort_order');
    }
}
