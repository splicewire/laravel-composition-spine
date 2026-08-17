<?php

namespace Splicewire\Composition\Tests\Stubs;

use Splicewire\Composition\Contracts\AngleContributor;

/**
 * A real (non-neutral) angle contributor: it carries an intent, sections, an encouraged component and a
 * prompt fragment, so composing it produces a non-neutral shape.
 */
class FeatureAngle implements AngleContributor
{
    public function angle(): StubAngle
    {
        return StubAngle::Feature;
    }

    public function intent(): ?StubIntent
    {
        return StubIntent::Act;
    }

    public function sections(): array
    {
        return ['Open on the feature', 'Close on the payoff'];
    }

    public function groundingEmphasis(): array
    {
        return [];
    }

    public function encouragedComponents(): array
    {
        return [StubComponent::Widget];
    }

    public function promptFragment(): string
    {
        return 'FEATURE ANGLE: write it as a feature.';
    }

    public function titleGuidance(): string
    {
        return 'Lead the title with the feature.';
    }
}
