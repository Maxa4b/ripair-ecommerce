@extends('layouts.app')

@section('content')
    <section class="services">
        <div class="support-card">
            <h1 class="section-title" style="text-align:left;margin-top:0;">Demande d'accès PRO</h1>
            <p>Accédez aux tarifs HT, remises ateliers et export commandes.</p>
            <form method="POST" action="{{ route('pro.apply.store') }}" class="filter-card" style="box-shadow:none;border:none;padding:0;margin-top:1.5rem;">
                @csrf
                <div class="filter-group">
                    <input name="company_name" placeholder="Raison sociale" class="catalog-input">
                </div>
                <div class="filter-group">
                    <input name="siret" placeholder="SIRET" class="catalog-input">
                </div>
                <div class="filter-group">
                    <input name="vat_number" placeholder="TVA intracom (optionnel)" class="catalog-input">
                </div>
                <div class="filter-group">
                    <input name="contact_name" placeholder="Contact" class="catalog-input">
                </div>
                <div class="filter-group">
                    <input name="email" type="email" placeholder="Email" class="catalog-input">
                </div>
                <div class="filter-group">
                    <input name="phone" placeholder="Téléphone" class="catalog-input">
                </div>
                <div class="filter-group">
                    <textarea name="message" placeholder="Activité, volumes, attentes..." rows="4" class="catalog-input"></textarea>
                </div>
                <button class="btn" style="width:100%;">Envoyer ma demande</button>
            </form>
        </div>
    </section>
@endsection
