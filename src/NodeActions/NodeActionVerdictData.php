<?php

namespace Splicewire\Composition\NodeActions;

use Schemastud\DataSchemas\Attributes\Title;
use Spatie\LaravelData\Data;

/** Medium-owned hard and soft signals; absent soft evidence is the medium's explicit policy. */
#[Title('Node action verdict')]
class NodeActionVerdictData extends Data
{
    /** @param list<string> $reasons @param array<string, int|float|null> $before @param array<string, int|float|null> $after */
    public function __construct(public bool $hardFail, public bool $softWorse, public array $reasons = [], public array $before = [], public array $after = []) {}

    public function keeps(): bool
    {
        return ! $this->hardFail && ! $this->softWorse;
    }
}
