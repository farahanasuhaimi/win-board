<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;

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
        $certPath = env('CURL_CA_BUNDLE', 'C:\Users\IGS\cacert.pem');

        Socialite::extend('google', function ($app) use ($certPath) {
            $config = $app['config']['services.google'];
            return Socialite::buildProvider(
                \Laravel\Socialite\Two\GoogleProvider::class,
                $config
            )->setHttpClient(new \GuzzleHttp\Client([
                'verify' => $certPath,
            ]));
        });
    }
}
