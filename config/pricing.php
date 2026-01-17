<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pricing constants
    |--------------------------------------------------------------------------
    |
    | These values describe the operational costs used to compute the public
    | price from a supplier HT cost. Adjust them if shipping or tax rules
    | change so that catalog prices stay accurate.
    |
    */

    // Cost to bring the part from MobileSentrix to the workshop (HT)
    'shipping_inbound' => 6.90,

    // Estimated fast delivery from workshop to customer (Colissimo/Chronopost)
    'shipping_outbound' => 7.25,

    // Packaging, materials, insurance per order (HT)
    'packaging' => 0.80,

    // Provision for defects/returns based on supplier HT price
    'failure_rate' => 0.015,

    // Target net margin applied on full cost structure
    'margin_rate' => 0.30,

    // Micro-enterprise social charges (URSSAF) to provision
    'micro_social_rate' => 0.21,

    // Future VAT rate so public price remains the same after switching status
    'vat_rate' => 0.20,

    // Plafond de prix HT vs coût base pour éviter des multiplicateurs excessifs
    'max_multiplier' => 3.0,

    // Seuil de panier TTC pour offrir la livraison
    'free_shipping_min_total' => 30.0,

    // Frais minimum appliqué si total < seuil gratuit (ex: coût outbound)
    'small_order_shipping_fee' => 7.25,

    // Surcoût fixe appliqué à chaque option de livraison (ex: frais fournisseur)
    'shipping_surcharge' => 7.95,
];
