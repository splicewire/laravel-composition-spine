<?php

namespace Splicewire\Composition\NodeActions;

/** A local medium adapter. No callbacks or model instances cross the wire. */
interface NodeActionGate
{
    /** Measure before generation; failures leave the subject untouched. */
    public function before(): void;

    /** Evaluate the applied candidate using the medium's existing policy. */
    public function after(): NodeActionVerdictData;
}
