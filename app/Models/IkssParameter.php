<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IkssParameter extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'entry_levels' => 'array',
            'aggregate_to_levels' => 'array',
            'formula_config' => 'array',
            'is_result' => 'boolean',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'include_in_report' => 'boolean',
        ];
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function group()
    {
        return $this->belongsTo(IkssParameterGroup::class, 'group_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function dependencies()
    {
        return $this->hasMany(IkssParameterDependency::class, 'parameter_id')->orderBy('sort_order');
    }

    public function inputs()
    {
        return $this->hasMany(IkssParameterInput::class, 'parameter_id');
    }

    public function results()
    {
        return $this->hasMany(IkssParameterResult::class, 'parameter_id');
    }
}
