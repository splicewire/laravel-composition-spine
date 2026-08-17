<?php

namespace Splicewire\Composition\Angles;

use RuntimeException;
use Splicewire\Composition\Contracts\AngleContract;
use Splicewire\Composition\Contracts\AngleContributor;

/**
 * Merges an ordered set of angles into one {@see ComposedShape}. The caller's ordered picks are honoured
 * in order; angles without a registered contributor are skipped, and an empty result falls back to a
 * neutral base contributor resolved from the `composition-engine.fallback_angle` config. Registering a
 * new contributor is all a future angle needs — the merge and every downstream consumer are
 * angle-agnostic.
 *
 * The composer is vocabulary-agnostic: it keys contributors by their {@see AngleContract::value()}
 * string and never names a concrete angle. It knows *that* there is a neutral fallback, never *which*.
 */
class AngleComposer
{
    /** @var array<string, AngleContributor> */
    private array $registry = [];

    private string $fallbackKey;

    /**
     * @param  iterable<AngleContributor>|null  $contributors  when null, resolved from
     *                                                         composition-engine.angle_contributors config
     */
    public function __construct(?iterable $contributors = null)
    {
        if ($contributors === null) {
            $contributors = array_map(
                fn (string $class): AngleContributor => app($class),
                config('composition-engine.angle_contributors', []),
            );
        }

        // The neutral fallback is always available even when not listed in angle_contributors.
        $fallback = $this->resolveFallback();
        $this->fallbackKey = $fallback->angle()->value();
        $this->registry[$this->fallbackKey] = $fallback;

        foreach ($contributors as $contributor) {
            $this->registry[$contributor->angle()->value()] = $contributor;
        }
    }

    /**
     * @param  array<int, AngleContract|string>  $angles  ordered, primary first
     */
    public function compose(array $angles): ComposedShape
    {
        $contributors = [];

        foreach ($angles as $angle) {
            $key = $angle instanceof AngleContract ? $angle->value() : $angle;

            if (! is_string($key) || ! isset($this->registry[$key]) || isset($contributors[$key])) {
                continue;
            }

            $contributors[$key] = $this->registry[$key];
        }

        if ($contributors === []) {
            $contributors[$this->fallbackKey] = $this->registry[$this->fallbackKey];
        }

        // The shape is neutral when it resolved to the neutral fallback contributor ALONE — whether
        // because no requested angle matched the registry, or because the caller requested the fallback
        // angle explicitly. Either way the piece carries no real editorial angle. Recorded here because
        // the fallback contributor is not empty (it carries sections and a prompt fragment), so
        // neutrality cannot be derived downstream from emptiness — it must be stamped at composition time.
        $neutralBase = array_keys($contributors) === [$this->fallbackKey];

        return $this->merge(array_values($contributors), $neutralBase);
    }

    private function resolveFallback(): AngleContributor
    {
        $class = config('composition-engine.fallback_angle');

        if (! is_string($class) || $class === '') {
            throw new RuntimeException(
                'composition-engine.fallback_angle must be set to a neutral AngleContributor class-string.'
            );
        }

        return app($class);
    }

    /**
     * @param  array<int, AngleContributor>  $contributors  primary first
     * @param  bool  $neutralBase  true when $contributors is the neutral fallback alone
     */
    private function merge(array $contributors, bool $neutralBase = false): ComposedShape
    {
        $orderedAngles = [];
        $sections = [];
        $emphasis = [];
        $components = [];
        $fragments = [];

        foreach ($contributors as $contributor) {
            $orderedAngles[] = $contributor->angle();

            $this->mergeUnique($sections, $contributor->sections());
            $this->mergeUnique($emphasis, $contributor->groundingEmphasis());
            $this->mergeUnique($components, $contributor->encouragedComponents());

            if (trim($contributor->promptFragment()) !== '') {
                $fragments[] = $contributor->promptFragment();
            }
        }

        return new ComposedShape(
            angles: $orderedAngles,
            intent: $contributors[0]->intent(),
            sections: $sections,
            groundingEmphasis: $emphasis,
            encouragedComponents: $components,
            promptFragment: implode("\n\n", $fragments),
            titleGuidance: trim($contributors[0]->titleGuidance()),
            neutralBase: $neutralBase,
        );
    }

    /**
     * @param  array<int, mixed>  $into
     * @param  array<int, mixed>  $values
     */
    private function mergeUnique(array &$into, array $values): void
    {
        foreach ($values as $value) {
            if (! in_array($value, $into, true)) {
                $into[] = $value;
            }
        }
    }
}
