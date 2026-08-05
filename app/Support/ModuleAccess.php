<?php

namespace App\Support;

use App\Models\Module;
use App\Models\User;
use App\Notifications\PublicAccountBlocked;
use Illuminate\Support\Facades\Schema;

final class ModuleAccess
{
    private const ACTIONS = ['view', 'list', 'create', 'edit', 'delete', 'manage'];

    public static function allowsPermission(User $user, string $permission): bool
    {
        if (self::expirePublicAccountIfNeeded($user)) {
            return false;
        }

        if (! $user->is_active || $user->blocked_at !== null) {
            return false;
        }

        if ($user->is_master_admin) {
            return true;
        }

        $module = self::moduleForPermission($permission);

        if (! $module) {
            return $user->can($permission);
        }

        if (! $module->is_active || $module->default_access_policy === Module::ACCESS_PRIVATE) {
            return false;
        }

        if ($user->is_public_account) {
            return self::isReadPermission($permission)
                && self::allowsPublicUserForModule($user, $module);
        }

        if (self::isDashboardPermission($permission)) {
            return true;
        }

        return $user->can($permission);
    }

    public static function moduleVisibleToUser(User $user, Module $module): bool
    {
        if (self::expirePublicAccountIfNeeded($user)) {
            return false;
        }

        if (! $user->is_active || $user->blocked_at !== null) {
            return false;
        }

        if ($user->is_master_admin) {
            return true;
        }

        if (! $module->is_active || ! $module->is_visible_in_menu) {
            return false;
        }

        if ($module->default_access_policy === Module::ACCESS_PRIVATE) {
            return false;
        }

        if ($user->is_public_account) {
            return self::allowsPublicUserForModule($user, $module);
        }

        if ($module->slug === 'list-dashboard') {
            return true;
        }

        return $module->children->isNotEmpty() || $user->can($module->slug);
    }

    public static function expirePublicAccountIfNeeded(User $user, bool $notify = false): bool
    {
        if (! $user->is_public_account || ! $user->public_access_expires_at) {
            return false;
        }

        if ($user->public_access_expires_at->isFuture()) {
            return false;
        }

        if ($user->is_active || $user->blocked_at === null) {
            $user->forceFill([
                'is_active' => false,
                'blocked_at' => $user->blocked_at ?? now(),
                'blocked_reason' => $user->blocked_reason ?? 'public_access_expired',
            ])->save();

            if ($notify) {
                $user->notify(new PublicAccountBlocked());
            }
        }

        return true;
    }

    public static function defaultPublicAccountExpiresAt(): \Illuminate\Support\Carbon
    {
        if (! Schema::hasTable('modules')) {
            return now()->addDays(15);
        }

        $hasThirtyDayModule = Module::query()
            ->where('is_active', true)
            ->where('default_access_policy', Module::ACCESS_TRIAL_30)
            ->exists();

        return now()->addDays($hasThirtyDayModule ? 30 : 15);
    }

    public static function moduleForPermission(string $permission): ?Module
    {
        if (! Schema::hasTable('modules')) {
            return null;
        }

        $slug = self::permissionToModuleSlug($permission);

        return Module::query()
            ->where('slug', $slug)
            ->orWhere('slug', $permission)
            ->first();
    }

    private static function permissionToModuleSlug(string $permission): string
    {
        $parts = explode('-', $permission, 2);

        if (count($parts) === 2 && in_array($parts[0], self::ACTIONS, true)) {
            return 'list-'.$parts[1];
        }

        return $permission;
    }

    private static function isReadPermission(string $permission): bool
    {
        return str_starts_with($permission, 'view-')
            || str_starts_with($permission, 'list-')
            || $permission === 'iso-9001';
    }

    private static function isDashboardPermission(string $permission): bool
    {
        return in_array($permission, ['view-dashboard', 'list-dashboard'], true);
    }

    private static function allowsPublicUserForModule(User $user, Module $module): bool
    {
        $startedAt = $user->public_access_started_at ?? $user->created_at ?? now();

        return match ($module->default_access_policy) {
            Module::ACCESS_PUBLIC => ! self::publicAccountExpired($user),
            Module::ACCESS_TRIAL_15 => $startedAt->copy()->addDays(15)->isFuture(),
            Module::ACCESS_TRIAL_30 => $startedAt->copy()->addDays(30)->isFuture(),
            default => false,
        };
    }

    private static function publicAccountExpired(User $user): bool
    {
        return $user->public_access_expires_at
            ? $user->public_access_expires_at->isPast()
            : false;
    }
}
