<?php

namespace App\Providers;

use App\Models\EquipmentReturn;
use App\Models\Loan;
use App\Policies\LoanPolicy;
use App\Policies\ReturnPolicy;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
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
        Carbon::setLocale('id');
        Paginator::useTailwind();

        Gate::policy(Loan::class, LoanPolicy::class);
        Gate::policy(EquipmentReturn::class, ReturnPolicy::class);
    }
}
