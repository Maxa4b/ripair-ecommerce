<?php

return [
    // Driver IA : 'none' (local), 'openai' (API OpenAI-compatible)
    'driver' => env('RHCATALOGO_DRIVER', 'none'),

    // Génération description : 'template' (recommandé), 'llm'
    'description_mode' => env('RHCATALOGO_DESCRIPTION_MODE', 'template'),

    // Libellé : validation/fallback anti-contresens (recommandé avec petits modèles locaux)
    'label_validation' => env('RHCATALOGO_LABEL_VALIDATION', true),

    // Si true et driver=openai : échoue si aucun libellé LLM exploitable (pas de fallback)
    'require_llm_label' => env('RHCATALOGO_REQUIRE_LLM_LABEL', false),

    // Si true : en cas de perte de connexion au LLM (ex: PC/SSH tunnel down), le pipeline se met en pause.
    'pause_on_llm_unavailable' => env('RHCATALOGO_PAUSE_ON_LLM_UNAVAILABLE', false),

    // Delai (s) entre deux probes pendant la pause.
    'pause_check_seconds' => env('RHCATALOGO_PAUSE_CHECK_SECONDS', 15),

    // Timeout (s) du probe /v1/models pendant la pause.
    'pause_probe_timeout' => env('RHCATALOGO_PAUSE_PROBE_TIMEOUT', 3),

    // Pour les jobs queue: delai (s) avant retry si LLM indisponible.
    'queue_retry_delay' => env('RHCATALOGO_QUEUE_RETRY_DELAY', 60),

    // Config OpenAI
    'openai' => [
        'endpoint' => env('RHCATALOGO_OPENAI_ENDPOINT', 'https://api.openai.com/v1/chat/completions'),
        'key' => env('RHCATALOGO_OPENAI_KEY'),
        'model' => env('RHCATALOGO_OPENAI_MODEL', 'gpt-4o-mini'),
        'timeout' => env('RHCATALOGO_OPENAI_TIMEOUT', 20),
    ],
];
