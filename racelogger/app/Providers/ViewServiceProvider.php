<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\World;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('*', function ($view) {

            $activeWorld = null;

            if (session()->has('active_world_id')) {
                $activeWorld = World::find(session('active_world_id'));
            }

            $view->with('activeWorld', $activeWorld);
        });
    }
}
