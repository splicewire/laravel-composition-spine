<?php

use Splicewire\Composition\NodeActions\Takes\ArtifactNodeTakeData;
use Splicewire\Composition\NodeActions\Takes\NodeTakeCollectionData;
use Splicewire\Composition\NodeActions\Takes\RevisionNodeTakeData;

it('round trips distinct revision and music artifact takes for one addressed node without inventing provenance', function () {
    $wire = ['data' => [
        ['kind' => 'revision', 'id' => '12', 'address' => 'node-7', 'cause' => 'refine', 'correlation' => null, 'recordedAt' => null,
            'before' => ['truth' => ['body' => 'Original']], 'producer' => null, 'fingerprint' => null],
        ['kind' => 'artifact', 'id' => 'cf000000-0000-4000-a000-000000000001', 'address' => 'node-7', 'medium' => 'music',
            'model' => 'music-model', 'provider' => 'music-provider', 'status' => 'completed', 'resultUrl' => null,
            'seconds' => 42, 'completedAt' => '2026-11-15T01:00:00Z', 'fingerprint' => null],
    ]];
    $takes = NodeTakeCollectionData::from($wire);

    expect($takes->data[0])->toBeInstanceOf(RevisionNodeTakeData::class)
        ->and($takes->data[1])->toBeInstanceOf(ArtifactNodeTakeData::class)
        ->and($takes->data[0]->id)->toBe('12')
        ->and($takes->data[0]->before->truth)->toBe(['body' => 'Original'])
        ->and($takes->data[0]->producer)->toBeNull()
        ->and($takes->data[1]->resultUrl)->toBeNull()
        ->and($takes->data[1]->fingerprint)->toBeNull()
        ->and($takes->toArray())->toEqual($wire);
});
