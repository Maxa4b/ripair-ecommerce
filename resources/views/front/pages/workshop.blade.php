@extends('layouts.app', ['title' => 'Atelier RIPAIR'])

@section('content')
    <section class="services">
        <div class="support-card">
            <h1 class="section-title" style="text-align:left;margin-top:0;">Atelier &amp; retrait</h1>
            <p>Adresse : {{ $content['address'] ?? '' }}</p>
            <p>Horaires : {{ $content['schedule'] ?? '' }}</p>
            <ul class="info-list" style="margin-top:1.5rem;">
                @foreach ($content['services'] ?? [] as $service)
                    <li>{{ $service }}</li>
                @endforeach
            </ul>
        </div>
    </section>
@endsection
