<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IkssParameterGroup extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function parameters()
    {
        return $this->hasMany(IkssParameter::class, 'group_id')->orderBy('sort_order');
    }
}
