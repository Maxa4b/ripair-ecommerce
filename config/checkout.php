<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Checkout configuration
    |--------------------------------------------------------------------------
    |
    | allow_dev_mode : expose l'option "Mode dev" au checkout pour bypass
    | le paiement et marquer la commande comme payée. Laisser à false en
    | production et l'activer uniquement pour les tests/recettes.
    |
    */
    'allow_dev_mode' => (bool) env('CHECKOUT_ALLOW_DEV_MODE', false),

    // Fenêtre (en heures) pour rattacher automatiquement une commande invitée
    // à un compte qui vient d'être créé / connecté (matching par e-mail).
    'guest_order_claim_hours' => (int) env('CHECKOUT_GUEST_ORDER_CLAIM_HOURS', 72),
];
