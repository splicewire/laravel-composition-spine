<?php

namespace Splicewire\Composition\NodeActions\Takes;

use Schemastud\DataSchemas\Attributes\MorphUnion;
use Spatie\LaravelData\Attributes\PropertyForMorph;
use Spatie\LaravelData\Contracts\PropertyMorphableData;
use Spatie\LaravelData\Data;

/** A read projection only: a replaced truth and a produced artifact keep separate identities. */
#[MorphUnion(discriminator: 'kind', arms: ['revision' => RevisionNodeTakeData::class, 'artifact' => ArtifactNodeTakeData::class])]
abstract class NodeTakeData extends Data implements PropertyMorphableData
{
    public function __construct(#[PropertyForMorph] public string $kind) {}

    public static function morph(array $properties): ?string
    {
        return match ($properties['kind'] ?? null) {
            'revision' => RevisionNodeTakeData::class,
            'artifact' => ArtifactNodeTakeData::class,
            default => null,
        };
    }
}
