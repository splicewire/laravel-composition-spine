<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Keyword prefix
    |--------------------------------------------------------------------------
    |
    | The namespace applied to this engine's private x-* schema keywords
    | (x-{prefix}-beat, x-{prefix}-generate, …), keeping them out of the shared
    | OpenAPI x- commons. Base/standard vocabulary (@id, x-dereference) is NOT
    | prefixed. Since the beam/satellite/tower seams firmed (ADR-0092, RCH-34) the
    | prefix follows the owning package's PRODUCT TIER, not a per-engine namespace:
    | composition-spine is a paid splicewire/* engine, so its prefix is `splice`
    | (emitting x-splice-*). The legacy per-engine prefix `swc` (and the older bare
    | empty prefix) still READ via KeywordVocabulary::detectPrefix() — dual-read.
    |
    */
    'keyword_prefix' => env('COMPOSITION_KEYWORD_PREFIX', 'splice'),
];
