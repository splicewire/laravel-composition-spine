<?php

namespace Splicewire\Composition\Tests\Stubs;

use Splicewire\Composition\Attributes\Beat;
use Splicewire\Composition\Attributes\BeatKind;
use Splicewire\Composition\Attributes\Cache;
use Splicewire\Composition\Attributes\Generate;
use Splicewire\Grounding\Attributes\Ground;
use Splicewire\Composition\Attributes\MaxDepth;
use Splicewire\Composition\Attributes\Pause;
use Splicewire\Composition\Attributes\Polish;
use Splicewire\Composition\Attributes\Prose;
use Splicewire\Composition\Attributes\ProseRole;
use Spatie\LaravelData\Data;

/**
 * A leaf declaring the trailing generation attributes at CLASS level — the ones BeatGrammar
 * used to silently drop (`#[Pause]`/`#[Polish]`/`#[Cache]`/`#[MaxDepth]`) alongside the
 * always-read `#[Beat]`/`#[Generate]`/`#[Ground]`. Proves the class-level projection is now
 * complete and single-sourced through the same bindings the property level uses.
 */
#[Beat(BeatKind::Expandable)]
#[Generate]
#[Ground]
#[Pause]
#[Polish(auto: false)]
#[Cache(scope: 'snapshot')]
#[MaxDepth(3)]
class ClassLevelBeatData extends Data
{
    public function __construct(
        #[Prose(ProseRole::Subject)]
        public string $interpretation = '',
    ) {}
}
