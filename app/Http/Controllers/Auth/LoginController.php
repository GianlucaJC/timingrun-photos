<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Mostra il form di login.
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Gestisce il tentativo di login.
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        // Reindirizza all'area fotografi dopo il login
        return redirect()->intended(route('photographer.events.index'));
    }

    /**
     * Esegue il logout dell'utente.
     */
    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // Reindirizza alla pagina di login
        return redirect()->route('auth.login');
    }
}
