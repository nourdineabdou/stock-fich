<?php

namespace App\Http\Middleware;

use App\Support\PermissionCatalog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Chaque action (route) exige sa propre permission, voir PermissionCatalog. */
class CheckRoutePermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $permission = PermissionCatalog::permissionFor($request->route()?->getName());

        if ($user && $permission && ! $user->can($permission)) {
            abort(403, 'Accès refusé : votre rôle n\'a pas la permission « ' . PermissionCatalog::label($permission) . ' » (' . $permission . ').');
        }

        return $next($request);
    }
}
