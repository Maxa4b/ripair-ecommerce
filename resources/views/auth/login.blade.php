<x-guest-layout>
    <h1>Connexion</h1>
    <p class="lead">Accédez à votre espace RIPAIR pour suivre vos commandes.</p>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

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

    <form class="auth-form" method="POST" action="{{ route('login') }}">
        @csrf
        <input type="hidden" name="redirect" value="{{ old('redirect', $redirect ?? request('redirect')) }}">
        <div class="form-row">
            <label for="email">Email</label>
            <input id="email" class="auth-input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        </div>

        <div class="form-row">
            <label for="password">Mot de passe</label>
            <div class="password-field">
                <input id="password" class="auth-input" type="password" name="password" required autocomplete="current-password">
                <button type="button" class="toggle-password" data-toggle="password" aria-label="Afficher ou masquer le mot de passe">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                        <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" stroke="currentColor"/>
                        <circle cx="12" cy="12" r="3" stroke="currentColor"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="auth-actions">
            <label class="auth-checkbox" for="remember">
                <input id="remember" type="checkbox" name="remember">
                <span>Rester connecté</span>
            </label>
            @if (Route::has('password.request'))
                <a class="auth-secondary" style="color:#3abafc;" href="{{ route('password.request') }}">Mot de passe oublié ?</a>
            @endif
        </div>

        <div class="auth-actions" style="justify-content: space-between;">
            <span style="color:#475569;font-size:14px;">Nouveau chez RIPAIR ? <a class="auth-secondary" style="color:#3abafc;" href="{{ route('register', ['redirect' => old('redirect', $redirect ?? request('redirect'))]) }}">Créer un compte</a></span>
            <button type="submit" class="auth-btn">Se connecter</button>
        </div>
    </form>
</x-guest-layout>
