<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IkssParameterResultItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function parameterResult()
    {
        return $this->belongsTo(IkssParameterResult::class, 'parameter_result_id');
    }
}

