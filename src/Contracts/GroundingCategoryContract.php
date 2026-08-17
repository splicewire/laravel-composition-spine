<?php

namespace Splicewire\Composition\Contracts;

/**
 * A grounding category's identity. The app supplies the concrete vocabulary (a backed enum whose cases
 * are the categories its grounding providers cover — characters, locations, titles, etc.); the engine
 * only needs the stable string identity to key providers, order emphasis, and classify context. A
 * reusable engine cannot enumerate one app's grounding taxonomy, so it ships the contract, not the enum.
 */
interface GroundingCategoryContract
{
    /**
     * The stable string identity of this grounding category (e.g. "locations", "watch").
     */
    public function value(): string;
}
