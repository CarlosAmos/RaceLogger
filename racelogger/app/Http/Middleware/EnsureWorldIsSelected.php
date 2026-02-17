<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureWorldIsSelected
{
    public function handle(Request $request, Closure $next)
    {
        if (!session()->has('active_world_id')) {
            return redirect()->route('world.select');
        }

        return $next($request);
    }
}

