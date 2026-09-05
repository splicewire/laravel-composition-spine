<?php

use Splicewire\Composition\Io\StructuredExport;
use Splicewire\Composition\Wire;

// The generated transport adapter returns self while delegating hydration to the spine factory.
// Keep this test transport-independent: Saloon and the SDK must not become spine dependencies.
class StructuredExportAdapterRegression extends StructuredExport
{
    public static function fromPayload(array $payload): self
    {
        return static::fromArray($payload);
    }
}

class CompositionAdapterRegression extends Wire\Composition {}
class IntakeEntityAdapterRegression extends Wire\IntakeEntity {}
class TenantAdapterRegression extends Wire\Tenant {}
class CoherenceAdapterRegression extends Wire\CoherenceAnalysis {}
class SchemaAdapterRegression extends Wire\SchemaDescriptor {}
class MediaAdapterRegression extends Wire\MediaRef {}
class ExportAdapterRegression extends Wire\ExportResult {}
class QueryAdapterRegression extends Wire\GroundingQuery {}
class SnapshotAdapterRegression extends Wire\GroundingSnapshot {}
class NotificationAdapterRegression extends Wire\PublishNotification {}

it('hydrates a structured export into the calling adapter without losing normalization', function () {
    $payload = [
        'schema' => ['type' => 'object'],
        'nodes' => [9 => ['type' => 'beat', 'fields' => ['title' => 'One'], 'groundingTokens' => [4 => 'source:1']]],
        'cost_usd' => '1.25',
        'cost_by_meter' => ['generation' => 1.25],
    ];
    $adapter = StructuredExportAdapterRegression::fromPayload($payload);

    expect($adapter)->toBeInstanceOf(StructuredExportAdapterRegression::class)
        ->and($adapter)->toEqual(new StructuredExportAdapterRegression(
            schema: ['type' => 'object'],
            nodes: [['type' => 'beat', 'fields' => ['title' => 'One'], 'groundingTokens' => ['source:1'], 'path' => '0']],
            costUsd: 1.25,
            costByMeter: ['generation' => 1.25],
        ))
        ->and(get_object_vars($adapter))->toBe(get_object_vars(StructuredExport::fromArray($payload)));
});

it('keeps empty structured export defaults on the adapter subtype', function () {
    expect(StructuredExportAdapterRegression::fromPayload([]))
        ->toEqual(new StructuredExportAdapterRegression(null, []));
});

it('preserves the calling subtype for every mapped wire factory', function (string $adapter) {
    expect($adapter::fromArray([]))->toBeInstanceOf($adapter);
})->with([
    CompositionAdapterRegression::class,
    IntakeEntityAdapterRegression::class,
    TenantAdapterRegression::class,
    CoherenceAdapterRegression::class,
    SchemaAdapterRegression::class,
    MediaAdapterRegression::class,
    ExportAdapterRegression::class,
    QueryAdapterRegression::class,
    SnapshotAdapterRegression::class,
    NotificationAdapterRegression::class,
]);

it('preserves the grounding snapshot subtype when appending a group', function () {
    $original = SnapshotAdapterRegression::fromArray(['facts' => [['token' => 'one']]]);
    $appended = $original->withGroup('facts', [['token' => 'two']]);

    expect($appended)->toBeInstanceOf(SnapshotAdapterRegression::class)
        ->and($appended->toArray())->toBe(['facts' => [['token' => 'one'], ['token' => 'two']]])
        ->and($original->toArray())->toBe(['facts' => [['token' => 'one']]]);
});
