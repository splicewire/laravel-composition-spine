<?php

/**
 * AngleComposer on the shared mechanism: text is the DEGENERATE case of the one axis-keyed rule, because
 * it buckets by the angle's own identity. These cover the two things that move as a result — the shape
 * now carries a steering fingerprint, and the duplicate-key skip is now an in-axis override — plus the
 * one thing that must NOT move, which is everything else.
 */

use Splicewire\Composition\Angles\AngleComposer;
use Splicewire\Composition\Tests\Stubs\FeatureAngle;
use Splicewire\Composition\Tests\Stubs\NeutralAngle;
use Splicewire\Composition\Tests\Stubs\StubAngle;
use Splicewire\Composition\Wire\Composition;

beforeEach(function () {
    config([
        'splicewire.composition.angles.neutral_base' => NeutralAngle::class,
        'splicewire.composition.angles.contributors' => [FeatureAngle::class],
    ]);
});

it('stamps a steering fingerprint on the composed shape', function () {
    $shape = app(AngleComposer::class)->compose(['feature']);

    expect(array_keys($shape->steering['axes']))->toBe(['feature'])
        ->and($shape->steering['axes']['feature']['key'])->toBe('feature')
        ->and($shape->steering['axes']['feature']['hash'])->toMatch('/^[0-9a-f]{64}$/');
});

it('keys the text fingerprint by the angle itself, so nothing ever collides', function () {
    $shape = app(AngleComposer::class)->compose(['feature', 'neutral']);

    // Axis IS identity here. Layering, never overriding — the same rule music uses to override.
    expect(array_keys($shape->steering['axes']))->toBe(['feature', 'neutral']);
});

it('hangs no dials on a text fingerprint', function () {
    // Dials are a medium's own scalars; prose has none, so the key is absent rather than empty.
    expect(app(AngleComposer::class)->compose(['feature'])->steering)->not->toHaveKey('dials');
});

it('records the neutral fallback in the fingerprint when it composed alone', function () {
    $shape = app(AngleComposer::class)->compose([]);

    expect($shape->neutralBase)->toBeTrue()
        ->and(array_keys($shape->steering['axes']))->toBe(['neutral']);
});

it('stamps an empty fingerprint on the empty shape, rather than omitting it', function () {
    config(['splicewire.composition.angles.neutral_base' => null]);

    // An honestly empty shape has honestly empty steering; a consumer reading it gets a shape, not a
    // missing key it has to guard.
    expect(app(AngleComposer::class)->compose(['totally-made-up'])->steering)->toBe(['axes' => []]);
});

it('takes the first of a repeated angle and merges it once', function () {
    $repeated = app(AngleComposer::class)->compose(['feature', 'feature']);
    $once = app(AngleComposer::class)->compose(['feature']);

    // What was a bare duplicate-key skip is now an in-axis override. Identical outcome, which is the
    // point: the skip was always that override, it just had nothing to override.
    expect($repeated->angles)->toBe([StubAngle::Feature])
        ->and($repeated->sections)->toBe($once->sections)
        ->and($repeated->promptFragment)->toBe($once->promptFragment)
        ->and($repeated->steering)->toBe($once->steering);
});

it('moves the fingerprint when an angle contributes differently under an unchanged name', function () {
    $before = app(AngleComposer::class)->compose(['feature'])->steering;

    config(['splicewire.composition.angles.contributors' => [WordierFeatureAngle::class]]);
    app()->forgetInstance(AngleComposer::class);

    $after = app(AngleComposer::class)->compose(['feature'])->steering;

    // Attribution is the point: an edited section plan under the same angle name is a different shape,
    // and an identity-only fingerprint could not say so.
    expect($after['axes']['feature']['key'])->toBe($before['axes']['feature']['key'])
        ->and($after['axes']['feature']['hash'])->not->toBe($before['axes']['feature']['hash']);
});

it('carries steering across the wire onto a Composition', function () {
    $steering = ['axes' => ['feature' => ['key' => 'feature', 'hash' => str_repeat('a', 64)]]];

    expect(Composition::fromArray(['id' => 'c1', 'steering' => $steering])->steering)->toBe($steering)
        ->and(Composition::fromArray(['id' => 'c1'])->steering)->toBeNull();
});

/** The same angle, saying more. Only its contribution changed; its identity did not. */
class WordierFeatureAngle extends FeatureAngle
{
    public function sections(): array
    {
        return [...parent::sections(), 'And one more thing'];
    }
}
