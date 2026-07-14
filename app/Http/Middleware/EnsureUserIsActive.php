<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        // Checked on every request, not just at login, so voiding or deactivating a
        // user takes effect immediately instead of waiting for their session to lapse.
        $reason = match (true) {
            Auth::user()->isVoided() => 'This account has been voided.',
            ! Auth::user()->isActive() => 'Your account is inactive.',
            default => null,
        };

        if ($reason !== null) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => $reason]);
        }

        return $next($request);
    }
}
