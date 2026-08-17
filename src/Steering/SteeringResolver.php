<?php

namespace Splicewire\Composition\Steering;

/**
 * Folds declared contributions into the set that survives, by one rule:
 *
 *   **Contributions layer across axes and override within an axis. Declaration order is precedence.**
 *
 * That is the whole mechanism. It is medium-agnostic because it never opens a payload — one knob,
 * what the axis is, decides everything. Music buckets by a render axis, so an album's house mood and
 * a song's mood collide and the song (declared first) wins. Prose buckets by the angle's own
 * identity, so nothing ever collides and the same rule reads as pure layering.
 *
 * The generic tier was extracted FROM the music side, which has the richer algebra and the spike
 * mileage behind it; text is the degenerate case. Not the other way round.
 */
final class SteeringResolver
{
    /**
     * @param  iterable<SteeringContribution>  $contributions  ordered, most-specific first
     */
    public function resolve(iterable $contributions): ResolvedSteering
    {
        $survivors = [];

        foreach ($contributions as $contribution) {
            // First declaration on an axis wins; later ones are the override that never fires when
            // axis and identity are the same thing.
            if (isset($survivors[$contribution->axis])) {
                continue;
            }

            $survivors[$contribution->axis] = $contribution;
        }

        return new ResolvedSteering(array_values($survivors));
    }
}
