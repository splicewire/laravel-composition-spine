<?php

use Schemastud\DataSchemas\Attributes\Title;
use Schemastud\DataSchemas\Contracts\ProvidesEnumLabel;
use Splicewire\Composition\NodeActions\NodeActionDecisionData;
use Splicewire\Composition\NodeActions\NodeActionMode;
use Splicewire\Composition\NodeActions\NodeActionRevisionData;
use Splicewire\Composition\NodeActions\NodeActionSnapshotData;
use Splicewire\Composition\NodeActions\NodeActionVerdictData;

it('declares a title on each NodeAction data class', function (string $class, string $title) {
    $attributes = (new ReflectionClass($class))->getAttributes(Title::class);

    expect($attributes)->toHaveCount(1)
        ->and($attributes[0]->newInstance()->value)->toBe($title);
})->with([
    'decision' => [NodeActionDecisionData::class, 'Node action decision'],
    'revision' => [NodeActionRevisionData::class, 'Node action revision'],
    'snapshot' => [NodeActionSnapshotData::class, 'Node action snapshot'],
    'verdict' => [NodeActionVerdictData::class, 'Node action verdict'],
]);

it('declares a label for each NodeAction mode', function () {
    expect(is_subclass_of(NodeActionMode::class, ProvidesEnumLabel::class))->toBeTrue()
        ->and(array_map(static fn (NodeActionMode $mode): string => $mode->label(), NodeActionMode::cases()))
        ->toBe(['Refine', 'Regenerate']);
});
