<?php

namespace Splicewire\Composition\Steering;

use JsonPath\JsonObject;

/**
 * The one place the JSONPath engine is named. Everything else in {@see \Splicewire\Composition\Steering}
 * asks this class "does this scope cover this node?" and never sees a vendor type, mirroring the way
 * `JsonPathMutator` isolates the same library for DataOverlay (ADR-0089) so the engine stays swappable.
 *
 * `galbar/jsonpath` is reached transitively through `schemastud/laravel-data-schemas`, deliberately:
 * the steering tier was specified to add ZERO new dependencies, and this indirection is the reason a
 * later swap costs one file rather than a package-wide rewrite.
 *
 * A malformed scope raises rather than silently matching nothing. Silently dropping an unparseable
 * scope would re-create the fault scoping exists to prevent — a cue reaching a section it was never
 * meant for, or vanishing from one it was — with no signal that anything went wrong.
 */
final class JsonPathScope
{
    /**
     * Does `$scope` cover the node at `$nodePath` within `$document`?
     *
     * Matching is by VALUE: the scope is evaluated to its set of matched nodes and the node under test
     * is checked for membership. The engine returns matched values, not their paths, so value equality
     * is the available comparison — and it is the honest one, since two byte-identical sections are
     * indistinguishable to anything downstream that reads them.
     *
     * With `$nodePath` null the question degrades to "does this scope match anything at all here?" —
     * the document-level read, which is what a caller with no particular node in hand is asking.
     *
     * @param  array<mixed>  $document  the target document the scope is absolute against
     */
    public function covers(string $scope, array $document, ?string $nodePath = null): bool
    {
        $object = new JsonObject($document);

        $matched = $this->matches($object, $scope);

        if ($matched === []) {
            return false;
        }

        if ($nodePath === null) {
            return true;
        }

        $node = $this->matches($object, $nodePath);

        if ($node === []) {
            return false;
        }

        foreach ($node as $value) {
            if (! $this->contains($matched, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, mixed>
     */
    private function matches(JsonObject $object, string $path): array
    {
        $result = $object->get($path);

        // The engine reports "no match" as false in some configurations and [] in others; both mean
        // the same thing here.
        return is_array($result) ? $result : [];
    }

    /**
     * @param  array<int, mixed>  $haystack
     */
    private function contains(array $haystack, mixed $needle): bool
    {
        foreach ($haystack as $candidate) {
            if ($candidate === $needle) {
                return true;
            }
        }

        return false;
    }
}
