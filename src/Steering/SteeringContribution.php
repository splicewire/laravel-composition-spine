<?php

namespace Splicewire\Composition\Steering;

/**
 * One declared contribution to how a piece is steered — the atom the {@see SteeringResolver} folds.
 *
 * The one rule the mechanism enforces: contributions LAYER across axes and OVERRIDE within an axis,
 * and declaration order is precedence. Everything else here is either identity (what the fingerprint
 * records), read-time ordering, or medium-owned payload the mechanism never opens.
 *
 * The split that keeps this honest: the mechanism decides WHICH contributions survive; the payload
 * type decides HOW the survivors merge. Prose unions sections and joins prompt fragments; music
 * unions verbatim cue lines. Neither merge rule belongs here — putting merge knowledge in the
 * resolver is exactly why the spine's {@see \Splicewire\Composition\Angles\AngleComposer} could not
 * serve a second medium.
 */
final class SteeringContribution
{
    /**
     * @param  string  $axis  the bucket. Contributions on DIFFERENT axes layer; contributions on the
     *                        same axis override, first-declared winning. Text buckets by the angle's
     *                        own identity, so collisions never occur and it reads as pure layering —
     *                        the degenerate case of this rule, not a different rule.
     * @param  string  $key  stable identity of this contribution; what the fingerprint records
     *                       alongside the content hash.
     * @param  mixed  $payload  medium-owned. Never read here.
     * @param  int  $priority  read-time ordering under a cap — higher survives the cut. Distinct from
     *                         precedence, which is declaration order and decides survival outright.
     * @param  string|null  $scope  absolute RFC 9535 JSONPath against the TARGET document; null means
     *                              the contribution applies everywhere. Evaluated at READ time, per
     *                              node — which is why the document belongs to
     *                              {@see ResolvedSteering::effective()} and not to resolution.
     * @param  string|null  $notes  human documentation of WHY — most usefully, why a scope is drawn
     *                              where it is. Not part of the fingerprint: prose cannot change the
     *                              output, and two takes must not differ on a field that cannot.
     */
    public function __construct(
        public string $axis,
        public string $key,
        public mixed $payload,
        public int $priority = 0,
        public ?string $scope = null,
        public ?string $notes = null,
    ) {}
}
