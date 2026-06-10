<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IkssParameterDependency extends Model
{
    protected $guarded = [];

    public function parameter()
    {
        return $this->belongsTo(IkssParameter::class, 'parameter_id');
    }

    public function sourceParameter()
    {
        return $this->belongsTo(IkssParameter::class, 'source_parameter_id');
    }
}
