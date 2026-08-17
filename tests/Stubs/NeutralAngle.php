<?php

namespace Splicewire\Composition\Tests\Stubs;

use Splicewire\Composition\Angles\AngleComposer;
use Splicewire\Composition\Contracts\AngleContributor;

/**
 * The neutral fallback contributor. Like a host's General base it is NOT empty — it carries sections and
 * a prompt fragment — which is exactly why {@see AngleComposer}
 * must record neutrality on the shape rather than let consumers infer it from emptiness.
 */
class NeutralAngle implements AngleContributor
{
    public function angle(): StubAngle
    {
        return StubAngle::Neutral;
    }

    public function intent(): ?StubIntent
    {
        return null;
    }

    public function sections(): array
    {
        return ['A generic, well-structured skeleton'];
    }

    public function groundingEmphasis(): array
    {
        return [];
    }

    public function encouragedComponents(): array
    {
        return [];
    }

    public function promptFragment(): string
    {
        return 'Write a well-structured piece.';
    }

    public function titleGuidance(): string
    {
        return '';
    }
}
