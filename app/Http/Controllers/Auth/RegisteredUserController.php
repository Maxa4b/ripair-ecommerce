<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Commerce\CartService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
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

        return view('auth.register', [
            'redirect' => $redirect,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(
            [
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ],
            [
                'first_name.required' => 'Saisissez votre prénom.',
                'last_name.required' => 'Saisissez votre nom.',
                'email.required' => 'Saisissez votre adresse e-mail.',
                'email.email' => 'Saisissez une adresse e-mail valide (ex : prenom.nom@email.com).',
                'email.unique' => 'Cette adresse e-mail est déjà utilisée. Connectez-vous ou utilisez-en une autre.',
                'password.required' => 'Choisissez un mot de passe.',
                'password.confirmed' => 'Les deux mots de passe ne sont pas identiques.',
                'password.letters' => 'Le mot de passe doit contenir au moins une lettre.',
                'password.mixed' => 'Le mot de passe doit contenir au moins une majuscule et une minuscule.',
                'password.numbers' => 'Le mot de passe doit contenir au moins un chiffre.',
                'password.symbols' => 'Le mot de passe doit contenir au moins un caractère spécial (ex : ! @ # ?).',
            ],
            [
                'email' => 'adresse e-mail',
                'password' => 'mot de passe',
                'password_confirmation' => 'confirmation du mot de passe',
                'first_name' => 'prénom',
                'last_name' => 'nom',
            ]
        );

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        app(CartService::class)->claimSessionCartFor($user);
        app(\App\Services\Commerce\GuestOrderClaimService::class)->claimFor($user);

        $target = $request->input('redirect');
        if (! $target) {
            $target = route('account.dashboard', absolute: false);
        }

        return redirect()->intended($target);
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
