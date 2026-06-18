<?php

namespace App\Providers;
use App\Models\User;
use App\Models\Store;
use App\Models\Picture;
use App\Models\Product;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Relation::MorphMap([
            'users'    => User::class,
            'stores'   => Store::class,
            'products' => Product::class,
            'pictures' => Picture::class,
        ]);

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(500)->by($request->ip());
        });

        RateLimiter::for('orders', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
