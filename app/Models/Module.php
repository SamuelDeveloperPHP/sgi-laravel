<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = [
        'parent_id', 'name', 'slug', 'route_name', 
        'url', 'icon', 'is_active', 'is_visible_in_menu', 'order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_visible_in_menu' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Module::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Module::class, 'parent_id')->orderBy('order');
    }
}
