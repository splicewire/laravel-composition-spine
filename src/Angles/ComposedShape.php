<?php

namespace Splicewire\Composition\Angles;

use Splicewire\Composition\Contracts\AngleContract;
use Splicewire\Composition\Contracts\AngleContributor;
use Splicewire\Composition\Contracts\ComponentIntentContract;
use Splicewire\Composition\Contracts\ConversionIntentContract;
use Splicewire\Composition\Contracts\GroundingCategoryContract;

/**
 * The merged result of composing an ordered set of {@see AngleContributor}s.
 * The whole promotion pipeline reads this — the grounding emphasis orders the grounding sections, the
 * section plan and prompt fragment shape the body prompt, and the primary intent sets the primary CTA —
 * so layering a second angle changes only what the composer merges, never the consumers.
 */
class ComposedShape
{
    /**
     * @param  array<int, AngleContract>  $angles  ordered, primary first
     * @param  array<int, string>  $sections  ordered body section plan
     * @param  array<int, GroundingCategoryContract>  $groundingEmphasis  grounding categories, most-emphasised first
     * @param  array<int, ComponentIntentContract>  $encouragedComponents  abstract component intents
     * @param  bool  $neutralBase  true when the composer resolved to the neutral fallback contributor
     *                             alone (no requested angle matched). Recorded here because the fallback
     *                             is not empty — it carries sections and a prompt fragment — so
     *                             neutrality cannot be derived from emptiness; consumers (e.g. a
     *                             shape→beat-guidance stitcher) read this flag to emit no guidance.
     */
    public function __construct(
        public array $angles,
        public ?ConversionIntentContract $intent,
        public array $sections,
        public array $groundingEmphasis,
        public array $encouragedComponents,
        public string $promptFragment,
        public string $titleGuidance = '',
        public bool $neutralBase = false,
    ) {}

    public function primaryAngle(): AngleContract
    {
        return $this->angles[0];
    }

    public function hasAngle(AngleContract $angle): bool
    {
        return in_array($angle, $this->angles, true);
    }
}
