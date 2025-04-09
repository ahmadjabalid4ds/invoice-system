<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Uri;
use TomatoPHP\FilamentInvoices\Facades\FilamentInvoices;
use TomatoPHP\FilamentInvoices\Services\Contracts\InvoiceFor;
use TomatoPHP\FilamentInvoices\Services\Contracts\InvoiceFrom;
use App\Models\Account;
use App\Models\Customer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        URL::forceScheme(config("app.scheme"));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentInvoices::registerFrom([
            InvoiceFrom::make(Account::class)
                ->label(__('account'))
        ]);
        FilamentInvoices::registerFor([
            InvoiceFor::make(Customer::class)
                ->label(__('customer'))
        ]);
    }
}
