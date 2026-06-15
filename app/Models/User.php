<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

class User extends Authenticatable
{
    use Notifiable;

    protected static ?bool $moduleAccessTableExists = null;

    protected $fillable = [
        'employee_code',
        'role',
        'is_developer',
        'employee_id',
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_developer' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function moduleAccesses(): HasMany
    {
        return $this->hasMany(UserModuleAccess::class);
    }

    /**
     * Developer mode — akses semua modul + bypass validasi tertentu saat testing.
     * Fallback ke employee_code 'DEV' agar bekerja bahkan sebelum migration dijalankan.
     */
    public function isDeveloper(): bool
    {
        // Cek kolom is_developer jika sudah ada di DB
        $attrs = $this->getAttributes();
        if (array_key_exists('is_developer', $attrs)) {
            return (bool) $attrs['is_developer'];
        }
        // Fallback: employee_code = DEV → otomatis developer
        return strtoupper((string) ($this->employee_code ?? '')) === 'DEV';
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner' || $this->isDeveloper();
    }

    public function hasRole(string | array $roles): bool
    {
        $roles = (array) $roles;
        if ($this->isOwner()) {
            return true; // OWNER & DEV akses semua
        }

        return in_array($this->role, $roles, true);
    }

    public function canAccessModule(string $module): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        $module = strtolower(trim($module));

        // Admin selalu boleh akses production (tidak bisa di-override DB)
        if ($this->role === 'admin' && $module === 'production') {
            return true;
        }

        // Admin tidak boleh akses finance/marketplace/imports apapun setting DB-nya
        if ($this->role === 'admin' && in_array($module, ['accounting', 'marketplace', 'imports'], true)) {
            return false;
        }

        if (!self::moduleAccessTableExists()) {
            return in_array($module, self::defaultModulesForRole((string) $this->role), true);
        }

        $access = $this->relationLoaded('moduleAccesses')
            ? $this->moduleAccesses->firstWhere('module', $module)
            : $this->moduleAccesses()->where('module', $module)->first();

        if ($access) {
            return (bool) $access->can_access;
        }

        return in_array($module, self::defaultModulesForRole((string) $this->role), true);
    }

    public static function defaultModulesForRole(string $role): array
    {
        return match (strtolower($role)) {
            'owner' => array_keys(UserModuleAccess::MODULES),
            'admin' => ['dashboard', 'inventory', 'sales', 'purchasing', 'production'],
            'operating' => ['dashboard', 'inventory', 'production'],
            default => ['dashboard'],
        };
    }

    public static function moduleAccessTableExists(): bool
    {
        if (self::$moduleAccessTableExists !== null) {
            return self::$moduleAccessTableExists;
        }

        try {
            return self::$moduleAccessTableExists = Schema::hasTable('user_module_accesses');
        } catch (Throwable) {
            return self::$moduleAccessTableExists = false;
        }
    }

    public function preferredLandingRouteName(): ?string
    {
        $moduleRoutes = [
            'dashboard' => 'dashboard',
            'production' => 'production.dashboard',
            'sales' => 'sales.shipments.report',
            'accounting' => 'accounting.cash-basis-report.index',
            'inventory' => 'inventory.stocks.items',
            'purchasing' => 'purchasing.purchase_orders.index',
            'marketplace' => 'marketplace.orders.index',
            'imports' => 'imports.marketplace.index',
            'master' => 'master.items.index',
            'payroll' => 'payroll.dashboard',
            'costing' => 'costing.hpp.index',
        ];

        $preferredModules = match (strtolower((string) $this->role)) {
            'operating' => ['production', 'inventory', 'dashboard'],
            'admin' => ['sales', 'inventory', 'purchasing', 'marketplace', 'imports', 'accounting', 'dashboard'],
            'owner' => ['dashboard'],
            default => ['dashboard'],
        };

        $orderedModules = array_values(array_unique(array_merge($preferredModules, array_keys($moduleRoutes))));

        foreach ($orderedModules as $module) {
            $route = $moduleRoutes[$module] ?? null;
            if ($route && $this->canAccessModule($module) && Route::has($route)) {
                return $route;
            }
        }

        return null;
    }
}
