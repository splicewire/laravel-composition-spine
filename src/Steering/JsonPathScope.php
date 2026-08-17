<?php

namespace Splicewire\Composition\Steering;

use JsonPath\JsonObject;

/**
 * The one place the JSONPath engine is named. Everything else in {@see \Splicewire\Composition\Steering}
 * asks this class "does this scope cover this node?" and never sees a vendor type, mirroring the way
 * `JsonPathMutator` isolates the same library for DataOverlay (ADR-0089) so the engine stays swappable.
 */
final class JsonPathScope
{
    /**
     * Field written onto the nodes under test so they can be recognised in the scope's results. Nothing
     * outside this method sees it: it is added to a private copy of the document, and the copy is
     * discarded when the call returns.
     */
    private const PROBE = '__splicewire_steering_probe';

    /**
     * Does `$scope` cover the node(s) at `$nodePath` within `$document`?
     *
     * The engine reports matched VALUES, not their paths, so asking "is this node in the scope's result
     * set" by comparing values would answer wrongly for the ordinary case of a song with two identical
     * verses — every byte-identical node would stand in for every other. Instead the nodes under test are
     * MARKED in a throwaway copy of the document and the scope is then evaluated against it, so
     * membership is decided by position rather than by content.
     *
     * `$nodePath` may select more than one node — "the sections tagged VERSE", when a song has two. The
     * answer is then all-or-nothing: the scope must cover EVERY one of them. A contribution that is right
     * for the first verse and wrong for the second is not right for "VERSE", and letting it through on
     * the strength of the first is how a cue reaches a section it was never meant for.
     *
     * With `$nodePath` null the question degrades to "does this scope match anything at all here?" — the
     * document-level read, which is what a caller with no particular node in hand is asking.
     *
     * A malformed scope raises rather than silently matching nothing. Swallowing it would re-create the
     * fault scoping exists to prevent, with no signal that anything had gone wrong.
     *
     * @param  array<mixed>  $document  the target document the scope is absolute against
     */
    public function covers(string $scope, array $document, ?string $nodePath = null): bool
    {
        $object = new JsonObject($document);

        if ($nodePath === null) {
            return $this->matches($object, $scope) !== [];
        }

        // Mutates this call's own copy: JsonObject holds the array by value, so the caller's document is
        // untouched and no probe field can leak into a payload, a hash, or a render.
        $object->add($nodePath, true, self::PROBE);

        $targets = $this->countProbed($this->matches($object, $nodePath));

        // Zero targets means the path named nothing — or named something a field cannot be added to,
        // such as a scalar. Either way there is no node here for the scope to cover.
        if ($targets === 0) {
            return false;
        }

        return $this->countProbed($this->matches($object, $scope)) === $targets;
    }

    /**
     * @return array<int, mixed>
     */
    private function matches(JsonObject $object, string $path): array
    {
        $result = $object->get($path);

        // The engine reports "no match" as false in some configurations and [] in others; both mean the
        // same thing here.
        return is_array($result) ? $result : [];
    }

    /**
     * @param  array<int, mixed>  $nodes
     */
    private function countProbed(array $nodes): int
    {
        $count = 0;

        foreach ($nodes as $node) {
            if (is_array($node) && ($node[self::PROBE] ?? false) === true) {
                $count++;
            }
        }

        return $count;
    }
}
