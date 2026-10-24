<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::defaultView('partials.pagination');
        Paginator::defaultSimpleView('partials.pagination');

        // @money($amount) => $1,234.50 using the configured currency symbol
        Blade::directive('money', fn ($expression) => "<?php echo e(config('payroll.currency_symbol') . number_format((float) ({$expression}), 2)); ?>");
    }
}
