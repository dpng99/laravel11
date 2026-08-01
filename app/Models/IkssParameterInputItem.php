<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IkssParameterInputItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function parameterInput()
    {
        return $this->belongsTo(IkssParameterInput::class, 'parameter_input_id');
    }
}
