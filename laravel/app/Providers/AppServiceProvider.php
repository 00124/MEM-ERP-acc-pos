<?php

namespace App\Providers;

use App\Models\Company;
use Laravel\Cashier\Cashier;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Schema::defaultStringLength(191);
        // Cashier::ignoreMigrations();
        if (app_type() == 'saas') {
            Cashier::useCustomerModel(Company::class);
            Cashier::useSubscriptionModel(\App\SuperAdmin\Models\Subscription::class);
        }
        // Sanctum::ignoreMigrations();

        // For catching 404 Route not found error in vue app
        // Later in Base Controller we will disable it
        // Accroding to settings table app_debug column
        // config(['app.debug' => true]);npm
        // Setup in SmtpSettingsProvider
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // URL generation is handled automatically by TrustProxies middleware
        // which reads X-Forwarded-Host and X-Forwarded-Proto from the Replit proxy
    }
}
