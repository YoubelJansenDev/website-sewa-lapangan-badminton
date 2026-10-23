<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Project ini API murni: tidak ada halaman login untuk dialihkan.
     */
    protected function redirectTo(Request $request): ?string
    {
        return null;
    }
}