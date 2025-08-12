<?php

namespace App\Providers;

use AfricasTalking\SDK\AfricasTalking;
use Illuminate\Support\ServiceProvider;
use App\Services\BulkSMSService;
use App\Services\WhatsAppService;

class SMSServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register SMS Service
        $this->app->singleton('SMSService', function ($app) {
            $username = config('services.africastalking.username');
            $apiKey = config('services.africastalking.api_key');

            return new BulkSMSService($username, $apiKey);
        });

        // Register WhatsApp Service
        $this->app->singleton('WhatsAppService', function ($app) {
            return new WhatsAppService();
        });

        // Also bind the classes directly for dependency injection
        $this->app->bind(BulkSMSService::class, function ($app) {
            $username = config('services.africastalking.username');
            $apiKey = config('services.africastalking.api_key');

            return new BulkSMSService($username, $apiKey);
        });

        $this->app->bind(WhatsAppService::class, function ($app) {
            return new WhatsAppService();
        });
    }

    public function boot()
    {
        //
    }
}
