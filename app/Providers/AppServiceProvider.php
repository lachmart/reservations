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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Database\Eloquent\Model::unguard();

        \Illuminate\Support\Facades\Mail::extend('phpmail', fn () => new \App\Mail\Transport\PhpMailTransport);

        // Category menu in the site header. A broken DB must not take the
        // whole layout down with it, so it falls back to no menu.
        \Illuminate\Support\Facades\View::composer('layouts.app', function ($view) {
            $view->with('__menu', rescue(fn () => \App\Models\Category::menu(), fn () => collect(), report: false));
        });
    }
}
