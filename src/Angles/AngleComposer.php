<?php

namespace Splicewire\Composition\Angles;

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
 *
 * The fallback is resolved LAZILY — only when a requested key matches nothing registered, or when
 * nothing matched at all. Constructing a composer therefore needs no host config: a caller that always
 * passes real angles never touches it. It used to resolve in the constructor and THROW when unset,
 * which made an open package unusable without configuration it does not ship. A host that never needs
 * the neutral path may now leave it unset entirely, in which case an unmatched compose yields an empty
 * shape flagged `neutralBase` rather than an exception.
 */
class AngleComposer
{
    /** @var array<string, AngleContributor> */
    private array $registry = [];

    private ?AngleContributor $fallback = null;

    private bool $fallbackResolved = false;

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

            if (! is_string($key) || isset($contributors[$key])) {
                continue;
            }

            // The fallback stays addressable BY KEY even though it is no longer pre-registered, so
            // layering it onto a real angle still merges its sections and fragment. Dropping that would
            // have been a silent content change that the neutrality tests could not see.
            $contributor = $this->registry[$key] ?? null;

            if ($contributor === null && $this->fallback()?->angle()->value() === $key) {
                $contributor = $this->fallback();
            }

            if ($contributor !== null) {
                $contributors[$key] = $contributor;
            }
        }

        if ($contributors === []) {
            $fallback = $this->fallback();

            if ($fallback === null) {
                // No neutral base configured: an honestly empty shape, flagged neutral. Consumers read
                // the flag first (see ShapeBeatGuidance), so they short-circuit before the emptiness.
                return new ComposedShape(
                    angles: [],
                    intent: null,
                    sections: [],
                    groundingEmphasis: [],
                    encouragedComponents: [],
                    promptFragment: '',
                    neutralBase: true,
                );
            }

            $contributors[$fallback->angle()->value()] = $fallback;
        }

        // The shape is neutral when it resolved to the neutral fallback contributor ALONE — whether
        // because no requested angle matched the registry, or because the caller requested the fallback
        // angle explicitly. Either way the piece carries no real editorial angle. Recorded here because
        // the fallback contributor is not empty (it carries sections and a prompt fragment), so
        // neutrality cannot be derived downstream from emptiness — it must be stamped at composition time.
        // Compared by CLASS, read straight from config, so the flag never forces a resolve it did not
        // already need — and so it still holds when the fallback is ALSO listed in angle_contributors
        // and therefore matched through the registry rather than the fallback path.
        $fallbackClass = config('composition-engine.fallback_angle');

        $neutralBase = count($contributors) === 1
            && is_string($fallbackClass)
            && $fallbackClass !== ''
            && reset($contributors) instanceof $fallbackClass;

        return $this->merge(array_values($contributors), $neutralBase);
    }

    /**
     * The neutral base contributor, or null when the host configures none. Resolved at most once, and
     * only when a compose actually reaches for it.
     */
    private function fallback(): ?AngleContributor
    {
        if (! $this->fallbackResolved) {
            $this->fallbackResolved = true;

            $class = config('composition-engine.fallback_angle');
            $this->fallback = is_string($class) && $class !== '' ? app($class) : null;
        }

        return $this->fallback;
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
