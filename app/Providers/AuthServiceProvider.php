<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\ModelPayment;
use App\Policies\PaymentPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        ModelPayment::class => PaymentPolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();

        // ✅ Gate untuk approve-payment
        Gate::define('approve-payment', [PaymentPolicy::class, 'approvePayment']);
        Gate::define('reject-payment', [PaymentPolicy::class, 'rejectPayment']);
        Gate::define('approve-withdrawal', function ($user) {
            return $user->role === 'BENDAHARA';});
        Gate::define('approve-loan', function ($user) {
            return $user->role === 'KETUA';});
    }
}
