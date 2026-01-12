<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

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
        // Valeurs par défaut si la vue ne reçoit pas $sortOptions
        $defaultSortOptions = [
            'latest'     => 'Les plus récents',
            'price_asc'  => 'Prix croissant',
            'price_desc' => 'Prix décroissant',
        ];

        // Injecte la variable globalement si elle n'existe pas déjà
        View::composer('*', function ($view) use ($defaultSortOptions) {
            if (! $view->offsetExists('sortOptions')) {
                $view->with('sortOptions', $defaultSortOptions);
            }
        });
    }
}
