<x-guest-layout>
    <h1>Créer un compte</h1>
    <p class="lead">Profitez du suivi de commande, des adresses enregistrées et d’un support prioritaire.</p>

    @if ($errors->any())
        <div class="alert alert-error">
            <strong>Veuillez vérifier :</strong>
            <ul style="margin:6px 0 0 16px; padding:0; list-style:disc;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form class="auth-form" method="POST" action="{{ route('register') }}">
        @csrf
        <input type="hidden" name="redirect" value="{{ old('redirect', $redirect ?? request('redirect')) }}">
        <div class="form-row two">
            <div>
                <label for="first_name">Prénom</label>
                <input id="first_name" class="auth-input" type="text" name="first_name" value="{{ old('first_name') }}" required autofocus autocomplete="given-name">
            </div>
            <div>
                <label for="last_name">Nom</label>
                <input id="last_name" class="auth-input" type="text" name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name">
            </div>
        </div>

        <div class="form-row">
            <label for="email">Email</label>
            <input id="email" class="auth-input" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
        </div>

        <div class="form-row two">
            <div>
                <label for="password">Mot de passe</label>
                <div class="password-field">
                    <input id="password" class="auth-input" type="password" name="password" required autocomplete="new-password">
                    <button type="button" class="toggle-password" data-toggle="password" aria-label="Afficher ou masquer le mot de passe">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" stroke="currentColor"/>
                            <circle cx="12" cy="12" r="3" stroke="currentColor"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div>
                <label for="password_confirmation">Confirmation</label>
                <div class="password-field">
                    <input id="password_confirmation" class="auth-input" type="password" name="password_confirmation" required autocomplete="new-password">
                    <button type="button" class="toggle-password" data-toggle="password_confirmation" aria-label="Afficher ou masquer le mot de passe">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" stroke="currentColor"/>
                            <circle cx="12" cy="12" r="3" stroke="currentColor"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="auth-actions" style="justify-content: space-between;">
            <span style="color:#475569;font-size:14px;">Déjà client ? <a class="auth-secondary" href="{{ route('login', ['redirect' => old('redirect', $redirect ?? request('redirect'))]) }}">Se connecter</a></span>
            <button type="submit" class="auth-btn">Créer mon compte</button>
        </div>
    </form>
</x-guest-layout>
