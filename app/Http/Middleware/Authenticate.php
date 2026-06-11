<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Se la richiesta non è autenticata, reindirizza alla rotta di login corretta.
        // Il nome della rotta è 'auth.login' come definito in routes/web.php.
        return $request->expectsJson() ? null : route('auth.login');
    }
}