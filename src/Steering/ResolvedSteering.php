<?php

namespace Splicewire\Composition\Steering;

/**
 * The survivors of a {@see SteeringResolver} fold, plus whatever opaque dials the medium hung on the
 * set — the complete, recordable description of how a piece was steered.
 *
 * Resolution and reading are deliberately two moments. Resolution answers "which contributions
 * survive", once, with no document in hand. Reading answers "which of those reach THIS node", per
 * node, against the target document — which is why {@see self::effective()} takes the document and
 * {@see SteeringResolver::resolve()} does not.
 */
final class ResolvedSteering
{
    private JsonPathScope $scope;

    /**
     * @param  array<int, SteeringContribution>  $survivors  one per axis, in declaration order
     * @param  array<string, mixed>  $dials  medium-owned scalars (a finetune id, a conditioning
     *                                       reference, a seed…). OPAQUE: carried and fingerprinted,
     *                                       never read. The moment this tier knows what a finetune is,
     *                                       video and image start negotiating with it.
     */
    public function __construct(
        private array $survivors = [],
        private array $dials = [],
        ?JsonPathScope $scope = null,
    ) {
        $this->scope = $scope ?? new JsonPathScope;
    }

    /**
     * The same survivors carrying a different set of dials. The façade's handle: the resolver has no
     * dials in its signature because dials are not contributions and do not participate in the fold.
     *
     * @param  array<string, mixed>  $dials
     */
    public function withDials(array $dials): self
    {
        return new self($this->survivors, $dials, $this->scope);
    }

    /**
     * Every survivor, in declaration order — before any scope or cap is applied.
     *
     * @return array<int, SteeringContribution>
     */
    public function all(): array
    {
        return $this->survivors;
    }

    /**
     * The survivors that actually reach a given node, ordered for reading.
     *
     * Order is priority-descending, declaration order breaking ties, so a `$cap` drops from the
     * LOW-priority end — the load-bearing contributions survive a crowded set. The cap MECHANISM is
     * generic; the number never is, and neither is the unit. Here the unit is contributions. A façade
     * whose vendor limit counts something inside the payload (music's `positive_styles` counts cue
     * lines, not angles) applies that limit itself, after merging, and leaves this one null.
     *
     * A scoped contribution with no document to evaluate against is DROPPED, not kept. Keeping it
     * would resurrect the fault scoping exists to prevent: the original bug was a lyric cue reaching a
     * lyric-less instrumental section, and "we could not check, so we let it through" is that bug.
     *
     * @param  array<mixed>|null  $document  the target document scopes are absolute against
     * @param  string|null  $nodePath  JSONPath of the node being read; null asks the document-level
     *                                 question, "does this scope match anything here at all?"
     * @return array<int, SteeringContribution>
     */
    public function effective(?array $document = null, ?string $nodePath = null, ?int $cap = null): array
    {
        $applicable = [];

        foreach ($this->survivors as $index => $contribution) {
            if ($contribution->scope !== null) {
                if ($document === null || ! $this->scope->covers($contribution->scope, $document, $nodePath)) {
                    continue;
                }
            }

            $applicable[] = [$index, $contribution];
        }

        usort(
            $applicable,
            fn (array $a, array $b) => [$b[1]->priority, $a[0]] <=> [$a[1]->priority, $b[0]],
        );

        $ordered = array_map(fn (array $pair) => $pair[1], $applicable);

        return $cap === null ? $ordered : array_slice($ordered, 0, $cap);
    }

    /**
     * A compact, diffable record of how the piece was steered: `axis => {key, hash}`, plus the dials.
     *
     * The hash is what makes this reproducible. Identity alone is not: edit a cue next week, keep the
     * key, and an identity-only fingerprint is byte-identical while the take is not. So the hash
     * covers the resolved PAYLOAD together with the scope and priority that decided where and in what
     * order it landed — every input that can change the output, and nothing that cannot.
     *
     * @return array{axes: array<string, array{key: string, hash: string}>, dials?: array<string, mixed>}
     */
    public function fingerprint(): array
    {
        $axes = [];

        foreach ($this->survivors as $contribution) {
            $axes[$contribution->axis] = [
                'key' => $contribution->key,
                'hash' => $this->hash($contribution),
            ];
        }

        ksort($axes);

        $dials = array_filter($this->dials, fn (mixed $value) => $value !== null && $value !== []);
        ksort($dials);

        return $dials === [] ? ['axes' => $axes] : ['axes' => $axes, 'dials' => $dials];
    }

    private function hash(SteeringContribution $contribution): string
    {
        return hash('sha256', (string) json_encode($this->canonical([
            'payload' => $contribution->payload,
            'priority' => $contribution->priority,
            'scope' => $contribution->scope,
        ])));
    }

    /**
     * Key-sorted, array-shaped rendering of an arbitrary payload, so the hash is stable across runs
     * and across whatever order a medium happened to build its own structure in.
     */
    private function canonical(mixed $value): mixed
    {
        if (is_object($value)) {
            $value = json_decode((string) json_encode($value), true);
        }

        if (! is_array($value)) {
            return $value;
        }

        $value = array_map(fn (mixed $item) => $this->canonical($item), $value);

        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }
}
