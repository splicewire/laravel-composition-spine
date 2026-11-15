<?php

namespace Splicewire\Composition\NodeActions;

enum NodeActionMode: string
{
    case Refine = 'refine';
    case Regenerate = 'regenerate';
}
