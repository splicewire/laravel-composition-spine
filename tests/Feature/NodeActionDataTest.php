<?php

use Splicewire\Composition\NodeActions\NodeActionDecisionData;
use Splicewire\Composition\NodeActions\NodeActionRevisionData;
use Splicewire\Composition\NodeActions\NodeActionSnapshotData;
use Splicewire\Composition\NodeActions\NodeActionVerdictData;

it('round trips a node decision with truth and standing without a storage model', function () {
    $before = new NodeActionSnapshotData('body/paragraph-7', ['text' => 'Original'], ['locked' => true]);
    $after = new NodeActionSnapshotData('body/paragraph-7', ['text' => 'Rewritten'], ['locked' => false]);
    $decision = new NodeActionDecisionData('reverted', new NodeActionRevisionData('revision-1', $before, $after), new NodeActionVerdictData(false, true, ['quality_decreased'], ['score' => 8], ['score' => 4]));

    $wire = json_decode(json_encode($decision->toArray(), JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
    $restored = NodeActionDecisionData::from($wire);
    expect($restored->revision->before->standing)->toBe(['locked' => true])
        ->and($restored->revision->before->truth)->toBe(['text' => 'Original'])
        ->and($restored->revision->after->truth)->toBe(['text' => 'Rewritten'])
        ->and($restored->verdict->reasons)->toBe(['quality_decreased'])
        ->and($restored->decision)->toBe('reverted');
});
