<?php

namespace Splicewire\Composition\Contracts;

use Splicewire\Composition\Angles\AngleComposer;
use Splicewire\Composition\Angles\ComposedShape;

/**
 * One editorial angle's self-contained contribution to a piece of content's shape. The
 * {@see AngleComposer} merges an ordered set of these into a single {@see ComposedShape}; everything
 * downstream consumes the composed shape, never a single contributor — that is what lets a second angle
 * be layered on later as configuration rather than a rewrite.
 *
 * The contract is vocabulary-agnostic: it traffics in {@see AngleContract} / {@see ConversionIntentContract}
 * / {@see ComponentIntentContract}, which the app's concrete enums implement. Concrete contributors
 * (e.g. TravelAngle) live in the app.
 */
interface AngleContributor
{
    /**
     * The angle this contributor implements.
     */
    public function angle(): AngleContract;

    /**
     * The conversion intent this angle leads with, or null for the neutral base shape. Only the primary
     * angle's intent becomes the composed piece's primary call to action.
     */
    public function intent(): ?ConversionIntentContract;

    /**
     * Ordered, human-readable section descriptions this angle contributes to the body plan.
     *
     * @return array<int, string>
     */
    public function sections(): array;

    /**
     * Grounding categories this angle wants surfaced first (e.g. Locations, Characters, Watch).
     * The ordering is preserved so the most important categories appear earlier in the grounding.
     *
     * @return array<int, GroundingCategoryContract>
     */
    public function groundingEmphasis(): array;

    /**
     * Abstract component intents this angle encourages the body to use. Each intent owns its own
     * human-readable prompt label ({@see ComponentIntentContract::label()}), which the host-side
     * shape→beat-guidance stitcher reads when it lists the encouraged components.
     *
     * @return array<int, ComponentIntentContract>
     */
    public function encouragedComponents(): array;

    /**
     * A prompt fragment injected into the body system prompt describing how to write this angle.
     */
    public function promptFragment(): string;

    /**
     * How this angle's headline should read in its native shape (e.g. a counted list of locations,
     * "X vs Y", named characters). Only the primary angle's guidance reaches the composed shape; the
     * neutral base shape returns an empty string. Describes a preferred shape, not a rigid template.
     */
    public function titleGuidance(): string;
}
