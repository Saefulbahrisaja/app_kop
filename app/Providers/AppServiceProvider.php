<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\ModelPayment;
use App\Models\ModelPinjaman;
use App\Observers\LoanObserver;
use App\Observers\PaymentObserver;

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
        ModelPayment::observe(PaymentObserver::class);
        ModelPinjaman::observe(LoanObserver::class);
    }
}
