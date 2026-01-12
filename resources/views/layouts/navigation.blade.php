<nav x-data="{ open: false }" class="bg-white border-b border-slate-100">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-3">
        <div class="flex flex-1 items-center gap-8">
            <a href="{{ route('home') }}" class="text-xl font-semibold text-slate-900">RIPAIR</a>
            <div class="hidden items-center gap-6 text-sm font-medium text-slate-600 sm:flex">
                <a href="{{ route('catalog.index') }}" class="hover:text-slate-900">Catalogue</a>
                <a href="{{ route('cart.show') }}" class="hover:text-slate-900">Panier</a>
            </div>
        </div>

        <div class="hidden items-center gap-4 text-sm font-semibold sm:flex">
            @auth
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-4 py-2 text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                            <span>{{ auth()->user()->full_name ?? auth()->user()->email }}</span>
                            <svg class="h-4 w-4" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('account.dashboard')">Mon espace</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            @else
                <div class="space-x-4 text-sm">
                    <a href="{{ route('login') }}" class="text-slate-600 hover:text-slate-900">Connexion</a>
                    <a href="{{ route('register') }}" class="text-indigo-600 hover:text-indigo-700">Créer un compte</a>
                </div>
            @endauth
        </div>

        <div class="-me-2 flex items-center gap-3 sm:hidden">
            <button @click="open = ! open" class="inline-flex items-center justify-center rounded-md p-2 text-slate-500 hover:bg-slate-100">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            </div>
        </div>
    </div>

    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-slate-100 sm:hidden">
        <div class="space-y-1 px-6 py-4 text-sm font-medium text-slate-600">
            <a href="{{ route('catalog.index') }}" class="block py-2">Catalogue</a>
            <a href="{{ route('cart.show') }}" class="block py-2">Panier</a>
        </div>
        <div class="border-t border-slate-100 px-6 py-4 text-sm">
            @auth
                <p class="font-semibold text-slate-900">{{ auth()->user()->full_name ?? auth()->user()->email }}</p>
                <p class="text-slate-500">{{ auth()->user()->email }}</p>
                <a href="{{ route('account.dashboard') }}" class="mt-3 block text-indigo-600">Mon espace</a>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button class="text-left text-rose-600">Déconnexion</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="block text-slate-600">Connexion</a>
                <a href="{{ route('register') }}" class="mt-2 block text-indigo-600">Créer un compte</a>
            @endauth
        </div>
    </div>
</nav>
