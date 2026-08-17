<?php

/**
 * AngleComposer neutrality: the composed shape records WHETHER it resolved to the neutral fallback alone,
 * so downstream consumers (e.g. a shape→beat-guidance stitcher) can emit no guidance for a neutral shape
 * without comparing against any host `*::General` enum. Neutrality cannot be derived from emptiness — the
 * fallback contributor carries its own sections and prompt fragment — so it must be stamped here.
 */

use Splicewire\Composition\Angles\AngleComposer;
use Splicewire\Composition\Tests\Stubs\FeatureAngle;
use Splicewire\Composition\Tests\Stubs\NeutralAngle;
use Splicewire\Composition\Tests\Stubs\StubAngle;

beforeEach(function () {
    config([
        'composition-engine.fallback_angle' => NeutralAngle::class,
        'composition-engine.angle_contributors' => [FeatureAngle::class],
    ]);
});

it('flags a shape neutral when no requested angle matches (fallback only)', function () {
    $shape = app(AngleComposer::class)->compose([]);

    expect($shape->neutralBase)->toBeTrue()
        ->and($shape->primaryAngle())->toBe(StubAngle::Neutral)
        ->and($shape->intent)->toBeNull();
});

it('flags a shape neutral for an unknown angle key (fallback only)', function () {
    $shape = app(AngleComposer::class)->compose(['totally-made-up']);

    expect($shape->neutralBase)->toBeTrue()
        ->and($shape->primaryAngle())->toBe(StubAngle::Neutral);
});

it('does not flag a shape neutral when a requested angle matches', function () {
    $shape = app(AngleComposer::class)->compose(['feature']);

    expect($shape->neutralBase)->toBeFalse()
        ->and($shape->primaryAngle())->toBe(StubAngle::Feature)
        ->and($shape->neutralBase)->not->toBeTrue();
});

it('flags a shape neutral when the fallback angle is requested explicitly', function () {
    // Requesting the fallback angle by key resolves to the fallback contributor ALONE — the piece still
    // carries no real editorial angle, so it is neutral even though a requested angle "matched".
    $shape = app(AngleComposer::class)->compose(['neutral']);

    expect($shape->neutralBase)->toBeTrue()
        ->and($shape->primaryAngle())->toBe(StubAngle::Neutral);
});

it('does not flag neutral when a real angle is layered onto the explicit fallback', function () {
    $shape = app(AngleComposer::class)->compose(['feature', 'neutral']);

    expect($shape->neutralBase)->toBeFalse()
        ->and($shape->primaryAngle())->toBe(StubAngle::Feature);
});

it('keeps the fallback contributor non-empty, so neutrality is not inferable from emptiness', function () {
    $shape = app(AngleComposer::class)->compose([]);

    // The neutral shape is flagged, yet still carries sections + a fragment — proving the flag is load
    // bearing (a consumer reading emptiness would wrongly treat this as "has content").
    expect($shape->neutralBase)->toBeTrue()
        ->and($shape->sections)->not->toBeEmpty()
        ->and($shape->promptFragment)->not->toBe('');
});

/**
 * The fallback is OPTIONAL and LAZY. An open package must be constructible and usable without host
 * config it does not ship; previously the constructor resolved the fallback eagerly and threw when it
 * was unset, so a caller passing only real angles still could not build a composer.
 */
it('composes real angles with no fallback configured at all', function () {
    config(['composition-engine.fallback_angle' => null]);

    $shape = app(AngleComposer::class)->compose(['feature']);

    expect($shape->neutralBase)->toBeFalse()
        ->and($shape->primaryAngle())->toBe(StubAngle::Feature)
        ->and($shape->sections)->not->toBeEmpty();
});

it('yields an empty shape flagged neutral when nothing matches and no fallback is configured', function () {
    config(['composition-engine.fallback_angle' => null]);

    $shape = app(AngleComposer::class)->compose(['totally-made-up']);

    expect($shape->neutralBase)->toBeTrue()
        ->and($shape->angles)->toBe([])
        ->and($shape->primaryAngle())->toBeNull()
        ->and($shape->sections)->toBe([])
        ->and($shape->promptFragment)->toBe('');
});

/**
 * The regression this refactor could most easily have introduced: the fallback is no longer
 * pre-registered, so it must stay addressable BY KEY or layering it onto a real angle would silently
 * stop merging its sections and fragment — a content change no neutrality assertion would catch.
 */
it('still layers the explicitly-requested fallback content onto a real angle', function () {
    $layered = app(AngleComposer::class)->compose(['feature', 'neutral']);
    $featureOnly = app(AngleComposer::class)->compose(['feature']);

    expect($layered->neutralBase)->toBeFalse()
        ->and($layered->primaryAngle())->toBe(StubAngle::Feature)
        ->and(count($layered->sections))->toBeGreaterThan(count($featureOnly->sections))
        ->and($layered->promptFragment)->not->toBe($featureOnly->promptFragment);
});
