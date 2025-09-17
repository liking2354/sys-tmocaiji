<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // 配置分页视图
        \Illuminate\Pagination\Paginator::defaultView('pagination.bootstrap-4');
        \Illuminate\Pagination\Paginator::defaultSimpleView('pagination.simple-bootstrap-4');
    }
}