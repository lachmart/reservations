<?php

namespace App\Providers;

use Blueprint\Models\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Filament's password-reset mail is a queued notification, but the
        // FTP hosting runs no queue worker, so it would sit in the jobs table
        // forever. Send it right away (admin invites and "Forgot password?").
        $this->app->bind(
            \Filament\Auth\Notifications\ResetPassword::class,
            fn ($app, array $params) => (new \Filament\Auth\Notifications\ResetPassword($params['token']))->onConnection('sync'),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Database\Eloquent\Model::unguard();

        \Illuminate\Support\Facades\Mail::extend('phpmail', fn () => new \App\Mail\Transport\PhpMailTransport);

        // Category menu in the site header, with the ready-made sets listed
        // under Sety. A broken DB must not take the whole layout down with
        // it, so it falls back to no menu.
        \Illuminate\Support\Facades\View::composer('layouts.app', function ($view) {
            $menu = rescue(fn () => \App\Models\Category::menu(), fn () => collect(), report: false);
            $sety = $menu->firstWhere('slug', 'sety');
            $sets = $sety ? rescue(
                fn () => \App\Models\Product::inCategory($sety)->available()->orderBy('price_per_day')->get(['id', 'name', 'slug', 'price_per_day']),
                fn () => collect(),
                report: false,
            ) : collect();

            $view->with(['__menu' => $menu, '__sets' => $sets]);
        });
    }
}
