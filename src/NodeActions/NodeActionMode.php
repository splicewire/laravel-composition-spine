<?php

namespace Splicewire\Composition\NodeActions;

use Schemastud\DataSchemas\Contracts\ProvidesEnumLabel;

enum NodeActionMode: string implements ProvidesEnumLabel
{
    case Refine = 'refine';
    case Regenerate = 'regenerate';

    public function label(): string
    {
        return match ($this) {
            self::Refine => 'Refine',
            self::Regenerate => 'Regenerate',
        };
    }
}
