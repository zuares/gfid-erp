<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

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

    public function oauthIdentities(): HasMany
    {
        return $this->hasMany(OauthIdentity::class);
    }

    public function openAiConnection(): HasOne
    {
        return $this->hasOne(OpenAiConnection::class);
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

    /**
     * Sumber kebenaran tunggal untuk hak melihat harga/nilai pembelian.
     *
     * Yang boleh melihat harga PO/GRN (unit_price, subtotal, diskon, pajak,
     * grand_total, last_purchase_price, nilai persediaan, nominal jurnal/AP,
     * expense_account_id): owner, developer, admin, dan role accounting.
     *
     * Role operating TIDAK boleh melihat harga.
     * Dipakai di controller, blade, dan JSON/API sebagai satu gerbang.
     */
    public function canSeePurchasePrices(): bool
    {
        return $this->isOwner() || in_array(strtolower((string) $this->role), ['accounting', 'admin'], true);
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

        // Modul yang dikunci oleh role tidak bisa di-override lewat DB.
        $locked = self::lockedModulesForRole((string) $this->role);
        if (isset($locked[$module])) {
            return (bool) $locked[$module]['on'];
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
            'nta' => ['dashboard', 'purchasing'],
            'operating' => ['dashboard', 'inventory', 'production'],
            default => ['dashboard'],
        };
    }

    /**
     * Modul yang aksesnya "dikunci" oleh role (tidak bisa di-override lewat DB).
     * Sumber kebenaran tunggal untuk canAccessModule() dan halaman Access Control.
     *
     * @return array<string, array{on: bool, reason: string}>
     */
    public static function lockedModulesForRole(string $role): array
    {
        return match (strtolower($role)) {
            'admin' => [
                'production'  => ['on' => true,  'reason' => 'Admin selalu punya akses modul ini.'],
                'purchasing'  => ['on' => true,  'reason' => 'Admin selalu punya akses modul ini.'],
                'marketplace' => ['on' => true,  'reason' => 'Admin selalu punya akses modul ini.'],
                'accounting'  => ['on' => false, 'reason' => 'Admin tidak diizinkan mengakses modul ini.'],
                'imports'     => ['on' => true,  'reason' => 'Admin selalu punya akses modul ini.'],
            ],
            'nta' => [
                'purchasing' => ['on' => true, 'reason' => 'NTA diberi akses ke modul pembelian.'],
            ],
            default => [],
        };
    }

    /**
     * Status akses efektif per modul untuk user ini, lengkap dengan info kunci.
     * Menyatukan aturan owner + override admin + default role + setting DB, sehingga
     * tampilan checklist di halaman Access Control selalu cocok dengan canAccessModule().
     *
     * @return array<string, array{on: bool, locked: bool, reason: ?string}>
     */
    public function effectiveModuleAccess(): array
    {
        $modules  = array_keys(UserModuleAccess::MODULES);
        $defaults = self::defaultModulesForRole((string) $this->role);
        $locked   = self::lockedModulesForRole((string) $this->role);

        $explicit = $this->relationLoaded('moduleAccesses')
            ? $this->moduleAccesses->keyBy('module')
            : ($this->exists ? $this->moduleAccesses()->get()->keyBy('module') : collect());

        $out = [];
        foreach ($modules as $module) {
            if ($this->isOwner()) {
                $out[$module] = ['on' => true, 'locked' => true, 'reason' => 'Owner selalu punya akses penuh.'];
                continue;
            }

            if (isset($locked[$module])) {
                $out[$module] = [
                    'on'     => (bool) $locked[$module]['on'],
                    'locked' => true,
                    'reason' => $locked[$module]['reason'],
                ];
                continue;
            }

            $on = isset($explicit[$module])
                ? (bool) $explicit[$module]->can_access
                : in_array($module, $defaults, true);

            $out[$module] = ['on' => $on, 'locked' => false, 'reason' => null];
        }

        return $out;
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
        if (strtolower((string) $this->role) === 'nta' && Route::has('purchasing.purchase_requests.index') && $this->canAccessModule('purchasing')) {
            return 'purchasing.purchase_requests.index';
        }

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
            'nta' => ['purchasing', 'dashboard'],
            'admin' => ['sales', 'inventory', 'purchasing', 'marketplace', 'imports', 'accounting', 'dashboard'],
            'owner' => ['master'],
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
