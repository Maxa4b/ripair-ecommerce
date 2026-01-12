<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin - {{ $title ?? 'RIPAIR' }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen bg-slate-100">
        <aside class="w-64 bg-slate-900 text-white">
            <div class="px-6 py-6 text-lg font-semibold">RIPAIR Admin</div>
            <nav class="space-y-2 px-4 text-sm">
                <a href="{{ route('admin.dashboard') }}" class="block rounded px-3 py-2 hover:bg-slate-800">Dashboard</a>
                <a href="{{ route('admin.catalogue-produits.index') }}" class="block rounded px-3 py-2 hover:bg-slate-800">Catalogue</a>
                <a href="{{ route('admin.commandes.index') }}" class="block rounded px-3 py-2 hover:bg-slate-800">Commandes</a>
                <a href="{{ route('admin.stocks-mouvements.index') }}" class="block rounded px-3 py-2 hover:bg-slate-800">Stock</a>
                <a href="{{ route('admin.sav-rma.index') }}" class="block rounded px-3 py-2 hover:bg-slate-800">SAV</a>
                <a href="{{ route('admin.transporteurs.index') }}" class="block rounded px-3 py-2 hover:bg-slate-800">Transport</a>
                <a href="{{ route('admin.reports.index') }}" class="block rounded px-3 py-2 hover:bg-slate-800">Reporting</a>
            </nav>
        </aside>
        <main class="flex-1 p-8 flex flex-col min-h-screen">
            <div class="flex-1">
                <h1 class="mb-6 text-2xl font-semibold text-slate-900">{{ $title ?? '' }}</h1>

                @if (session('success'))
                    <div class="mb-6 rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {{ session('success') }}
                    </div>
                @endif

                @yield('content')
            </div>
            <div class="mt-8">
                @include('layouts.partials.footer')
            </div>
        </main>
    </body>
</html>
