<?php

namespace Splicewire\Composition\NodeActions\Takes;

/**
 * A producer-local reader. Hosts bind the subject and its existing authorization before invoking it.
 * The address is local to that binding, never a model class or an authority supplied by the client.
 * Implementations keep their own connection, ownership and view checks. No new mutation is implied.
 */
interface NodeTakeReader
{
    public function read(string $address): NodeTakeCollectionData;
}
