@php
    $selectedShippingId = $selectedShippingId ?? old('shipping_method', data_get($checkoutData, 'shipping.shipping_method'));
    $boxtalDebug = session('boxtal_debug');
    $boxtalResponse = session('boxtal_response');
    $homeOptions = collect($shippingOptions)->filter(fn($opt) => ! str_contains(strtolower($opt['name']), 'relay') && ! str_contains(strtolower($opt['name']), 'relais'));
    $relayOptions = collect($shippingOptions)->filter(fn($opt) => str_contains(strtolower($opt['name']), 'relay') || str_contains(strtolower($opt['name']), 'relais'));
    if ($homeOptions->isEmpty() && $relayOptions->isEmpty()) {
        $homeOptions = collect($shippingOptions);
    }
    $hasHome = $homeOptions->isNotEmpty();
    $hasRelay = $relayOptions->isNotEmpty();
    $defaultCheckedSet = false;
@endphp

@if(!$shippingOptions->count())
    <div class="step-alert step-alert--error">
        Aucune option Boxtal disponible. Vérifiez la clé API, l'adresse (pays/CP) ou réessayez.
    </div>
    @if($boxtalResponse || $boxtalDebug)
        <div class="step-alert" style="background:#f7f9ff;color:#0b1f4f;border:1px solid #dbeafe;">
            <strong>Debug Boxtal</strong><br>
            @if($boxtalDebug)
                <div style="margin-top:6px;">Message : {{ $boxtalDebug }}</div>
            @endif
            @if($boxtalResponse)
                <div style="margin-top:6px;">
                    <div>URL : {{ $boxtalResponse['base'] ?? '' }}</div>
                    <div>Statut HTTP : {{ $boxtalResponse['status'] ?? '' }}</div>
                    <div>Query : {{ isset($boxtalResponse['query']) ? http_build_query($boxtalResponse['query']) : '' }}</div>
                    @if(!empty($boxtalResponse['attempts']))
                        <div style="margin-top:4px;">Tentatives :
                            @foreach($boxtalResponse['attempts'] as $att)
                                [accept={{ $att['accept'] ?? 'none' }} status={{ $att['status'] ?? '?' }}]
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif
@else
    @if($hasHome)
        <p class="shipping-group-title">Livraison à domicile</p>
        <div class="shipping-card-grid">
            @foreach ($homeOptions as $option)
                @php
                    $isChecked = $selectedShippingId == $option['method_id'] || (! $selectedShippingId && ! $defaultCheckedSet);
                    if (! $selectedShippingId && ! $defaultCheckedSet) { $defaultCheckedSet = true; }
                    $delay = $option['delay'] ?? '';
                    $logo = null;
                    $chip = 'Transporteur';
                    if (strtoupper($option['operator'] ?? '') === 'COLI' || str_contains(strtolower($option['name']), 'colissimo')) {
                        $logo = 'https://dirigeants-entreprise.com/content/uploads/Apps-Colissimo.jpg';
                        $chip = 'Colissimo';
                    } elseif (strtoupper($option['operator'] ?? '') === 'CHRP') {
                        $logo = 'https://apps.oxatis.com/Files/112496/Img/11/Apps-Chronopost.jpg';
                        $chip = 'Chronopost';
                    } elseif (strtoupper($option['operator'] ?? '') === 'MONR') {
                        $logo = 'https://cdn1.oxatis.com/Files/112496/dyn-images/19/Apps-Mondial-relay.png?w=1200&h=1200';
                        $chip = 'Mondial Relay';
                    }
                @endphp
                <div>
                    <input class="ship-radio" type="radio" id="ship-{{ Str::slug($option['method_id']) }}" name="shipping_method" value="{{ $option['method_id'] }}" data-price="{{ $option['price'] }}" @checked($isChecked)>
                    <label class="shipping-card-modern" for="ship-{{ Str::slug($option['method_id']) }}">
                        <div class="left">
                            @if($logo)
                                <img src="{{ $logo }}" alt="logo {{ $chip }}" class="ship-logo">
                            @endif
                            <div>
                                <div class="ship-name">{{ $option['name'] }}</div>
                                @if($delay)
                                    <div class="ship-delay">Estimé : {{ $delay }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="ship-price">{{ number_format($option['price'], 2, ',', ' ') }} €</div>
                    </label>
                </div>
            @endforeach
        </div>
    @endif

    @if($hasRelay)
        <p class="shipping-group-title">Point relais</p>
        <div class="shipping-card-grid">
            @foreach ($relayOptions as $option)
                @php
                    $isChecked = $selectedShippingId == $option['method_id'] || (! $selectedShippingId && ! $defaultCheckedSet);
                    if (! $selectedShippingId && ! $defaultCheckedSet) { $defaultCheckedSet = true; }
                    $delay = $option['delay'] ?? '';
                    $logo = null;
                    $chip = 'Transporteur';
                    if (strtoupper($option['operator'] ?? '') === 'CHRP') {
                        $logo = 'https://apps.oxatis.com/Files/112496/Img/11/Apps-Chronopost.jpg';
                        $chip = 'Chronopost';
                    } elseif (strtoupper($option['operator'] ?? '') === 'MONR') {
                        $logo = 'https://cdn1.oxatis.com/Files/112496/dyn-images/19/Apps-Mondial-relay.png?w=1200&h=1200';
                        $chip = 'Mondial Relay';
                    } elseif (strtoupper($option['operator'] ?? '') === 'COLI' || str_contains(strtolower($option['name']), 'colissimo')) {
                        $logo = 'https://dirigeants-entreprise.com/content/uploads/Apps-Colissimo.jpg';
                        $chip = 'Colissimo';
                    }
                @endphp
                <div>
                    <input class="ship-radio" type="radio" id="ship-{{ Str::slug($option['method_id']) }}" name="shipping_method" value="{{ $option['method_id'] }}" data-price="{{ $option['price'] }}" @checked($isChecked)>
                    <label class="shipping-card-modern" for="ship-{{ Str::slug($option['method_id']) }}">
                        <div class="left">
                            @if($logo)
                                <img src="{{ $logo }}" alt="logo {{ $chip }}" class="ship-logo">
                            @endif
                            <div>
                                <div class="ship-name">{{ $option['name'] }}</div>
                                @if($delay)
                                    <div class="ship-delay">Estimé : {{ $delay }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="ship-price">{{ number_format($option['price'], 2, ',', ' ') }} €</div>
                    </label>
                </div>
            @endforeach
        </div>
    @endif
@endif
