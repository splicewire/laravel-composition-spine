<?php

namespace Splicewire\Composition\NodeActions;

/** Local persistence and generation hooks; authorization belongs to the caller. */
interface NodeActionSubject
{
    public function image(): NodeActionSnapshotData;

    /** Produce, persist and project a candidate in the medium's native order. */
    public function refine(string $instruction): void;

    /** Restore standing after the recorder restores truth and re-derives output. */
    public function restoreStanding(NodeActionSnapshotData $before): void;

    /** Draft first, then compare/write/record in the medium's own transaction boundary. */
    public function regenerate(NodeActionRecorder $recorder): NodeActionRevisionData;
}
