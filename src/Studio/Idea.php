<?php

namespace Splicewire\Composition\Studio;

/**
 * The medium-agnostic production-lifecycle contract for an idea — the seed a Composition is realized
 * from. Abstracts content-engine's `ContentIdea` Eloquent model. A host model implements this so the
 * studio lifecycle (idea → promoting → promoted) is engine-readable without binding to one schema.
 *
 * SPINE, not engine (api-surface-coherence 90): this interface moved down out of
 * `laravel-composition-engine` because ADR-0168's addendum defines the spine as *the contract an
 * implementation of the engine hangs on* — and a host binding that says `implements Idea` is exactly
 * such an implementation. While it lived in the engine, every binding had to class-load the engine to
 * declare its own model (`implements` resolves at class-DECLARATION time, unlike a `::class` or a
 * type-hint), which `splicewire/laravel-satellite-composition` structurally may not require. The engine
 * itself referenced this interface NOWHERE — measured, zero call sites outside its own declaration — so
 * the move costs the engine nothing and is namespace-neutral: engine and spine both declare the
 * `Splicewire\Composition\` PSR-4 prefix, so not one `use` statement in the estate changed.
 */
interface Idea
{
    public function ideaTitle(): string;

    public function ideaPitch(): string;

    public function ideaStatus(): IdeaStatus;

    /**
     * Editorial angle slugs this idea was framed under.
     *
     * @return list<string>
     */
    public function ideaAngles(): array;
}
