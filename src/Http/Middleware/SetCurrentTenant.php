<?php

namespace Lyre\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;


class SetCurrentTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        self::setCurrentTenant($user);

        return $next($request);
    }

    public static function setCurrentTenant($user)
    {
        if (method_exists($user, 'tenants')) {
            $tenant = $user->tenants()->first();

            if ($tenant) {
                $usingSpatieRoles = in_array(\Spatie\Permission\Traits\HasRoles::class, class_uses(\App\Models\User::class));

                if ($usingSpatieRoles) {
                    setPermissionsTeamId($tenant?->id);
                }

                // In future: allow switch via query, header, session, etc.
                // $tenantId = $request->header('X-Tenant-ID') ?? $request->cookie('tenant_id') ?? $tenant->id;

                app()->instance('tenant', $tenant);
            }
        } else {
            App::forgetInstance('tenant');
        }
    }
}
