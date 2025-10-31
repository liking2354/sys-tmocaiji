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
        // 配置分页视图 - 使用现代化分页组件
        \Illuminate\Pagination\Paginator::defaultView('pagination.modern');
        \Illuminate\Pagination\Paginator::defaultSimpleView('pagination.modern');
    }
}