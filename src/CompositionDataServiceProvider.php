<?php

namespace Splicewire\Composition;

use Illuminate\Support\ServiceProvider;

/**
 * Registers the composition generation-attributes strategy into the
 * laravel-data-schemas pipeline so `#[Beat]`/`#[Ground]`/`#[Generate]`/`#[Prose]`/
 * `#[Pause]` project to the `x-*` vendor keywords. Idempotent — the strategy is
 * appended once regardless of how many spine-aware packages boot.
 *
 * This is the single owner of that registration: the engine no longer registers it
 * (the attributes + strategy live here now), and any host or satellite that depends
 * on this package — directly or transitively through the client — gets the same one
 * read path for the generation grammar with no drift.
 */
class CompositionDataServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/composition-spine-data.php', 'composition-spine-data');

        // Angles are namespaced under the PRODUCT root, beside the engine's own
        // `splicewire.composition.*`, rather than under a package-shaped key. The two packages merge
        // disjoint sub-keys, so boot order does not matter. Declared here because this package owns
        // AngleComposer, ComposedShape and the AngleContributor port — the engine used to declare
        // config the spine read, which is the same inversion that had the port itself filed upward.
        $this->mergeConfigFrom(__DIR__.'/../config/composition-angles.php', 'splicewire.composition.angles');

        // The composer takes its vocabulary by injection and reads no config itself, so it stays a
        // plain object a test or a host can construct directly. Both keys are optional: a host with no
        // angles gets a composer that always yields the empty neutral shape, and nothing throws.
        $this->app->bind(Angles\AngleComposer::class, function (): Angles\AngleComposer {
            $neutralBase = config('splicewire.composition.angles.neutral_base');

            return new Angles\AngleComposer(
                contributors: array_map(
                    fn (string $class): Contracts\AngleContributor => $this->app->make($class),
                    (array) config('splicewire.composition.angles.contributors', []),
                ),
                neutralBase: is_string($neutralBase) && $neutralBase !== ''
                    ? $this->app->make($neutralBase)
                    : null,
            );
        });

        // The single keyword chokepoint, prefix driven by config. Bound as a singleton so every emit and
        // read site (across this package and the engine) resolves the same prefix.
        $this->app->singleton(KeywordVocabulary::class, fn () => new KeywordVocabulary(
            (string) config('composition-spine-data.keyword_prefix', 'splice'),
        ));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/composition-spine-data.php' => config_path('composition-spine-data.php'),
            ], 'composition-spine-data-config');
        }

        $strategies = config('data-schemas.strategies', []);

        if (! in_array(GenerationAttributesStrategy::class, $strategies, true)) {
            $strategies[] = GenerationAttributesStrategy::class;
            config(['data-schemas.strategies' => $strategies]);
        }
    }
}
