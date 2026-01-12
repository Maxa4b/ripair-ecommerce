<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Commerce\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): View
    {
        $redirect = $request->string('redirect')->toString();
        if (! $redirect) {
            $redirect = $this->previousInternalPath();
        }

        if ($redirect) {
            $request->session()->put('url.intended', $redirect);
        }

        return view('auth.login', [
            'redirect' => $redirect,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        if ($user = Auth::user()) {
            app(CartService::class)->claimSessionCartFor($user);
            app(\App\Services\Commerce\GuestOrderClaimService::class)->claimFor($user);
        }

        $target = $request->input('redirect');
        if (! $target) {
            $target = route('account.dashboard', absolute: false);
        }

        return redirect()->intended($target);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function previousInternalPath(): ?string
    {
        $previous = url()->previous();
        if (! $previous) {
            return null;
        }

        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        $prevHost = parse_url($previous, PHP_URL_HOST);

        if ($prevHost && $appHost && strcasecmp($prevHost, $appHost) !== 0) {
            return null;
        }

        $path = (string) (parse_url($previous, PHP_URL_PATH) ?? '/');
        $query = parse_url($previous, PHP_URL_QUERY);
        $relative = $path . ($query ? ('?'.$query) : '');

        foreach (['/login', '/register', '/dashboard', '/forgot-password', '/reset-password', '/email/verify'] as $blocked) {
            if (str_starts_with($path, $blocked)) {
                return null;
            }
        }

        return $relative;
    }
}
