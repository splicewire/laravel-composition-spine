<?php

namespace Splicewire\Composition\NodeActions;

/** A consumer adapts its existing append-only history; no second revision store. */
interface NodeActionRecorder
{
    public function record(NodeActionSubject $subject, NodeActionSnapshotData $before, string $cause, ?string $correlation = null): NodeActionRevisionData;

    /** Restore truth and re-derive projection; the executor restores the saved standing. */
    public function revert(NodeActionSubject $subject, NodeActionRevisionData $revision): void;
}
