<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Hostinger's php.ini has a broken Windows path for curl.cainfo — override with system bundle
        $candidates = [
            '/etc/ssl/certs/ca-certificates.crt',   // Debian/Ubuntu
            '/etc/pki/tls/certs/ca-bundle.crt',     // CentOS/RHEL
        ];
        foreach ($candidates as $path) {
            if (file_exists($path)) {
                putenv("CURL_CA_BUNDLE=$path");
                putenv("SSL_CERT_FILE=$path");
                break;
            }
        }
    }
}
