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

        if ($user && method_exists($user, 'tenants')) {
            logger("Setting current tenant for user", [$user->toArray()]);

            $tenant = $user->tenants()->first();

            if ($tenant) {
                logger("Setting current tenant to {$tenant?->id} - {$tenant?->name}");

                // TODO: Kigathi - August 14 2025 - Should only set permissions teamID if spatie/laravel-permission is installed
                setPermissionsTeamId($tenant?->id);

                // In future: allow switch via query, header, session, etc.
                // $tenantId = $request->header('X-Tenant-ID') ?? $request->cookie('tenant_id') ?? $tenant->id;

                App::instance('tenant', $tenant);
            }
        } else {
            logger("No tenant found for user or user is not authenticated. Skipping tenant setup.");
            App::forgetInstance('tenant');
        }


        return $next($request);
    }
}
