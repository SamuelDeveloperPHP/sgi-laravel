<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    public const ACCESS_PUBLIC = 'public';
    public const ACCESS_TRIAL_15 = 'trial_15';
    public const ACCESS_TRIAL_30 = 'trial_30';
    public const ACCESS_PRIVATE = 'private';

    protected $fillable = [
        'parent_id', 'name', 'slug', 'route_name', 
        'url', 'icon', 'is_active', 'is_visible_in_menu',
        'default_access_policy', 'order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_visible_in_menu' => 'boolean',
    ];

    public static function accessPolicies(): array
    {
        return [
            self::ACCESS_PUBLIC,
            self::ACCESS_TRIAL_15,
            self::ACCESS_TRIAL_30,
            self::ACCESS_PRIVATE,
        ];
    }

    public function parent()
    {
        return $this->belongsTo(Module::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Module::class, 'parent_id')->orderBy('order');
    }
}
