# Tenant modules

Optional business capabilities (restaurant, bar/pub, variants, etc.) live here. Core POS/inventory stays untouched when you add module #4, #5, … #1000.

## Folder layout

```
app/Modules/
  ModuleKeys.php              # string constants — one per module
  ModuleRegistry.php          # in-memory registry (singleton)
  Contracts/
    ModuleDefinition.php      # every module implements this
  {ModuleName}/
    {ModuleName}Module.php    # metadata + signup defaults
    Http/                     # optional — module controllers
    ...                       # services, policies, etc. as needed
config/modules.php            # list of module classes (registration)
```

## Adding a module (checklist)

1. **Constant** — add `public const YOUR_MODULE = 'your_module';` to `ModuleKeys.php` (and `all()` if used).

2. **Definition** — create `app/Modules/YourModule/YourModuleModule.php` implementing `ModuleDefinition`:
   - `key()`, `label()`, `description()`
   - `defaultEnabledFor(Business $business)` — signup suggestion only, not a lock
   - `defaultSettingsFor(Business $business)` — JSON sub-settings in `business_modules.settings`

3. **Register** — add the class to `config/modules.php`. Nothing else should need editing for the module to appear in Capabilities / Superadmin Modules.

4. **Gate** (if role-specific) — in `AuthServiceProvider`, e.g. `access-your-module` checking `$user->business->hasModule(ModuleKeys::YOUR_MODULE)`.

5. **Routes** — in `routes/web.php` inside the tenant group:
   ```php
   Route::middleware(['can:access-your-module', 'module:your_module'])
       ->prefix('your-feature')
       ->name('your-feature.')
       ->group(function () {
           // ...
       });
   ```
   Disabled module → **404** via `module:*` middleware.

6. **Blade** — hide nav with `@module('your_module')` or `@can('access-your-module')`.

7. **Sub-settings** — if the module needs toggles beyond on/off (like restaurant tables/waiters), extend `BusinessModuleService::updateCapabilities()` for that key only. Simple modules need only the `enabled` flag.

8. **Legacy JSON** — only the original three modules dual-write to `businesses.settings`. New modules use `business_modules` only.

9. **Billing** — unchanged until Phase 6; no price fields on modules yet.

## Reference implementation

See `app/Modules/Appointments/` — preview module with route, gate, controller, and Capabilities checkbox. Remove from `config/modules.php` when no longer needed.

## Runtime API

```php
$business->hasModule('restaurant');
$business->moduleSetting('restaurant', 'use_tables', false);
app(BusinessModuleService::class)->capabilityStates($business);
```

## Do not

- Hard-code module lists in views (loop `$capabilities` or `ModuleRegistry::all()`).
- Gate features with `business_type` at runtime (presets only).
- Add per-module pricing until billing Phase 6.
