<?php

namespace App\Providers;
use App\Model\User;
use App\Models\Store;
use App\Models\Picture;
use App\Models\Product;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

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
        Relation::MorphMap([
            'users'=>User::class,
            'stores'=>Store::class,
            'products'=>Product::class,
            'pictures'=>Picture::class,
        ]);
    }
}
