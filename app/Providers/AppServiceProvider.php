<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Super Admin : tout. Compte sans aucun role (ancien compte) : acces complet pour ne bloquer
        // personne, tant qu'un role ne lui a pas ete attribue dans Utilisateurs.
        Gate::before(function ($user) {
            if ($user->hasRole('Super Admin') || $user->roles->isEmpty()) {
                return true;
            }

            return null;
        });
    }
}
