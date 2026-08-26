<?php

namespace Splicewire\Composition\Studio;

/**
 * The medium-agnostic contract for an editorial brief — the active directive a calendar plans under.
 * Abstracts content-engine's `EditorialBrief` Eloquent model; a host model implements it.
 *
 * SPINE, not engine (api-surface-coherence 90) — same reasoning as {@see Idea}: it is a contract host
 * bindings implement, the engine referenced it nowhere, and `implements` is a class-load position, so
 * homing it in the engine made `splicewire/laravel-satellite-composition` unable to declare
 * `CompositionBrief` without requiring a package ADR-0168 forbids it to require.
 */
interface Brief
{
    public function briefBody(): string;

    public function briefIsActive(): bool;
}
