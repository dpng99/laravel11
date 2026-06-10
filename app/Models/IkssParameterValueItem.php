<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IkssParameterValueItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function parameterValue()
    {
        return $this->belongsTo(IkssParameterValue::class, 'parameter_value_id');
    }
}
