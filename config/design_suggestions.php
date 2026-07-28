<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Motifs
    |--------------------------------------------------------------------------
    |
    | Mirrors resources/js/components/DesignArt.jsx's REGISTRY keys exactly
    | (same mirrored-list pattern as config/custom_design.php's allowed-motifs
    | on the Custom Design Studio epic) so the nightly generator only ever
    | rotates across motifs the frontend actually knows how to render. Keep
    | both lists in sync if a motif is ever added/removed/renamed on the JS
    | side — DesignArt.jsx's REGISTRY is the source of truth.
    |
    */

    'motifs' => [
        'star-of-david',
        'menorah',
        'chai',
        'shalom',
        'hamsa',
        'pomegranate',
        'aleph',
        'olive-branch',
        'hebrew-script',
    ],

    /*
    |--------------------------------------------------------------------------
    | Batch Size
    |--------------------------------------------------------------------------
    |
    | How many candidates DesignSuggestionGenerator produces per batch, both
    | on the nightly schedule and for the admin "Generate now" button.
    |
    */

    'batch_size' => (int) env('DESIGN_SUGGESTIONS_BATCH_SIZE', 20),

];
