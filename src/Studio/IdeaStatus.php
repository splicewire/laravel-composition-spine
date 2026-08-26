<?php

namespace Splicewire\Composition\Studio;

/**
 * The lifecycle status of an {@see Idea} (absorbed content-engine ContentIdeaStatus). Medium-agnostic.
 *
 * SPINE, not engine (api-surface-coherence 90): it is {@see Idea::ideaStatus()}'s return type, so it is
 * part of the same contract and travels with it. Unlike the interfaces it accompanies this enum was
 * never a class-load fatal on its own — a return type is inert — but splitting a contract across two
 * packages is how the next fatal gets built.
 */
enum IdeaStatus: string
{
    case Idea = 'idea';
    case Promoting = 'promoting';
    case Promoted = 'promoted';
    case Failed = 'failed';
}
