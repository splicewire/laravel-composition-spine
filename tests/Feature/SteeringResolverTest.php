<?php

use Splicewire\Composition\Steering\ResolvedSteering;
use Splicewire\Composition\Steering\SteeringContribution;
use Splicewire\Composition\Steering\SteeringResolver;

function contribution(
    string $axis,
    string $key,
    mixed $payload = ['cues' => ['cue']],
    int $priority = 0,
    ?string $scope = null,
): SteeringContribution {
    return new SteeringContribution(
        axis: $axis,
        key: $key,
        payload: $payload,
        priority: $priority,
        scope: $scope,
    );
}

/** A song document with one lyric-less section — the shape the scoping fault was found in. */
function songDocument(): array
{
    return ['sections' => [
        ['tag' => 'VERSE', 'lyrics' => 'the words'],
        ['tag' => 'CHORUS', 'lyrics' => 'more words'],
        ['tag' => 'INSTRUMENTAL'],
    ]];
}

function keysOf(array $contributions): array
{
    return array_map(fn (SteeringContribution $c) => $c->key, $contributions);
}

it('layers contributions across axes', function () {
    $resolved = (new SteeringResolver)->resolve([
        contribution('mood', 'warm'),
        contribution('production', 'dry'),
    ]);

    expect(keysOf($resolved->all()))->toBe(['warm', 'dry']);
});

it('overrides within an axis rather than stacking', function () {
    // Stacking is how you ask for a guitar and a ukulele in the same breath.
    $resolved = (new SteeringResolver)->resolve([
        contribution('instrumentation', 'song-guitar'),
        contribution('instrumentation', 'house-ukulele'),
    ]);

    expect(keysOf($resolved->all()))->toBe(['song-guitar']);
});

it('treats declaration order as precedence, most-specific first', function () {
    $resolved = (new SteeringResolver)->resolve([
        contribution('mood', 'song-mood'),
        contribution('mood', 'album-mood'),
        contribution('production', 'album-room'),
    ]);

    expect(keysOf($resolved->all()))->toBe(['song-mood', 'album-room']);
});

it('reads as pure layering when the axis IS the contribution identity', function () {
    // The text case: buckets by the angle's own identity, so the override never visibly fires.
    $resolved = (new SteeringResolver)->resolve([
        contribution('watch', 'watch'),
        contribution('travel', 'travel'),
    ]);

    expect(keysOf($resolved->all()))->toBe(['watch', 'travel']);
});

it('filters by JSONPath scope against the target document, per node', function () {
    $resolved = (new SteeringResolver)->resolve([
        contribution('diction', 'drawn-out', scope: '$..sections[?(@.lyrics)]'),
        contribution('production', 'dry'),
    ]);

    // "sections that have words" — not an enumeration of VERSE/CHORUS, which encodes the symptom of
    // the original fault (a lyric cue reaching a lyric-less outro) rather than its cause.
    expect(keysOf($resolved->effective(songDocument(), '$.sections[1]')))->toBe(['drawn-out', 'dry'])
        ->and(keysOf($resolved->effective(songDocument(), '$.sections[2]')))->toBe(['dry']);
});

it('drops a scoped contribution when there is no document to evaluate it against', function () {
    $resolved = (new SteeringResolver)->resolve([
        contribution('diction', 'drawn-out', scope: '$..sections[?(@.lyrics)]'),
        contribution('production', 'dry'),
    ]);

    // "We could not check, so we let it through" IS the bug scoping exists to prevent.
    expect(keysOf($resolved->effective()))->toBe(['dry']);
});

it('tells two byte-identical nodes apart when deciding scope', function () {
    $resolved = (new SteeringResolver)->resolve([
        contribution('diction', 'first-verse-only', scope: '$.sections[0]'),
    ]);

    $document = ['sections' => [
        ['tag' => 'VERSE', 'lyrics' => 'the words'],
        ['tag' => 'VERSE', 'lyrics' => 'the words'],
    ]];

    // Comparing matched VALUES would make every identical verse stand in for every other, and a scope
    // drawn around the first would silently apply to the second.
    expect(keysOf($resolved->effective($document, '$.sections[0]')))->toBe(['first-verse-only'])
        ->and(keysOf($resolved->effective($document, '$.sections[1]')))->toBe([]);
});

it('requires a scope to cover every node when the read names more than one', function () {
    $resolved = (new SteeringResolver)->resolve([
        contribution('diction', 'drawn-out', scope: '$..sections[?(@.lyrics)]'),
    ]);

    $document = ['sections' => [
        ['tag' => 'VERSE', 'lyrics' => 'the words'],
        ['tag' => 'VERSE'],
    ]];

    // "Right for the first verse, wrong for the second" is not right for VERSE. Letting it through on
    // the strength of the first is how a cue reaches a section it was never meant for.
    expect(keysOf($resolved->effective($document, '$.sections[?(@.tag == "VERSE")]')))->toBe([]);
});

it('answers the document-level question when no node is named', function () {
    $resolved = (new SteeringResolver)->resolve([
        contribution('diction', 'drawn-out', scope: '$..sections[?(@.lyrics)]'),
        contribution('mood', 'moody', scope: '$..sections[?(@.instrumental)]'),
    ]);

    expect(keysOf($resolved->effective(songDocument())))->toBe(['drawn-out']);
});

it('orders by priority and drops from the low-priority end under a cap', function () {
    $resolved = (new SteeringResolver)->resolve([
        contribution('mood', 'moody', priority: 0),
        contribution('vocal-identity', 'one-baritone', priority: 100),
    ]);

    expect(keysOf($resolved->effective(cap: 1)))->toBe(['one-baritone']);
});

it('breaks priority ties on declaration order', function () {
    $resolved = (new SteeringResolver)->resolve([
        contribution('production', 'dry'),
        contribution('mood', 'warm'),
        contribution('arrangement', 'sparse'),
    ]);

    expect(keysOf($resolved->effective()))->toBe(['dry', 'warm', 'sparse']);
});

it('fingerprints each axis as its surviving key plus a content hash', function () {
    $resolved = (new SteeringResolver)->resolve([
        contribution('mood', 'warm', ['cues' => ['tender']]),
    ]);

    $fingerprint = $resolved->fingerprint();

    expect(array_keys($fingerprint))->toBe(['axes'])
        ->and(array_keys($fingerprint['axes']))->toBe(['mood'])
        ->and($fingerprint['axes']['mood']['key'])->toBe('warm')
        ->and($fingerprint['axes']['mood']['hash'])->toMatch('/^[0-9a-f]{64}$/');
});

it('moves the fingerprint hash when the payload changes under an unchanged key', function () {
    $before = (new SteeringResolver)->resolve([contribution('mood', 'warm', ['cues' => ['tender']])]);
    $after = (new SteeringResolver)->resolve([contribution('mood', 'warm', ['cues' => ['tender', 'wistful']])]);

    // Identity alone does not reproduce a take: edit a cue, keep the key, and an identity-only
    // fingerprint is byte-identical while the render is not.
    expect($before->fingerprint()['axes']['mood']['key'])->toBe($after->fingerprint()['axes']['mood']['key'])
        ->and($before->fingerprint()['axes']['mood']['hash'])
        ->not->toBe($after->fingerprint()['axes']['mood']['hash']);
});

it('moves the fingerprint hash when the scope or the priority changes', function () {
    $base = (new SteeringResolver)->resolve([contribution('diction', 'drawn-out')]);
    $scoped = (new SteeringResolver)->resolve([contribution('diction', 'drawn-out', scope: '$..sections[?(@.lyrics)]')]);
    $ranked = (new SteeringResolver)->resolve([contribution('diction', 'drawn-out', priority: 10)]);

    $hash = fn (ResolvedSteering $r) => $r->fingerprint()['axes']['diction']['hash'];

    expect($hash($scoped))->not->toBe($hash($base))
        ->and($hash($ranked))->not->toBe($hash($base));
});

it('hashes a payload stably regardless of the order its keys were built in', function () {
    $a = (new SteeringResolver)->resolve([contribution('mood', 'warm', ['cues' => ['tender'], 'note' => 'x'])]);
    $b = (new SteeringResolver)->resolve([contribution('mood', 'warm', ['note' => 'x', 'cues' => ['tender']])]);

    expect($a->fingerprint())->toBe($b->fingerprint());
});

it('carries dials into the fingerprint without reading them', function () {
    $resolved = (new SteeringResolver)->resolve([contribution('mood', 'warm')])
        ->withDials(['seed' => 8813, 'finetune' => 'ft_band', 'condition_strength' => null, 'tags' => []]);

    // Sorted, nulls and empties dropped, so the record is stable across runs.
    expect($resolved->fingerprint()['dials'])->toBe(['finetune' => 'ft_band', 'seed' => 8813]);
});

it('omits dials entirely when the medium hung none on the set', function () {
    expect((new SteeringResolver)->resolve([contribution('mood', 'warm')])->fingerprint())
        ->toHaveKey('axes')
        ->not->toHaveKey('dials');
});

it('resolves an empty declaration to an empty set rather than raising', function () {
    $resolved = (new SteeringResolver)->resolve([]);

    expect($resolved->all())->toBe([])
        ->and($resolved->effective())->toBe([])
        ->and($resolved->fingerprint())->toBe(['axes' => []]);
});
