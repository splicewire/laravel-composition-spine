<?php

namespace Splicewire\Composition\NodeActions;

use Spatie\LaravelData\Data;

/** Untrusted generated truth: the caller must pass its own hard gate before offering or writing it. */
class NodeActionCandidateData extends Data
{
    /** @param array<string, mixed> $truth */
    public function __construct(public array $truth) {}
}
