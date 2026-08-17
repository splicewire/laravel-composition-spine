<?php

namespace Splicewire\Composition\Angles;

use Splicewire\Composition\Contracts\AngleContract;
use Splicewire\Composition\Contracts\AngleContributor;
use Splicewire\Composition\Steering\SteeringContribution;
use Splicewire\Composition\Steering\SteeringResolver;

/**
 * Merges an ordered set of angles into one {@see ComposedShape}. MANY angles, not one: the caller's
 * ordered picks are all merged — sections, grounding emphasis and encouraged components union across
 * them, while the FIRST requested angle supplies the composed intent and title guidance. Angles with no
 * registered contributor are skipped. Registering a new contributor is all a future angle needs; the
 * merge and every downstream consumer are angle-agnostic.
 *
 * The neutral base is the ZERO-angle case, not the one-angle case: the shape a piece carries when no
 * editorial angle applies, either because none was requested or because none matched.
 *
 * The composer is vocabulary-agnostic: it keys contributors by their {@see AngleContract::value()}
 * string and never names a concrete angle. It knows *that* there is a neutral base, never *which*.
 *
 * This class reads NO config and touches NO container. It used to do both — resolving
 * `composition-engine.angle_contributors` and `composition-engine.fallback_angle` through `app()` in
 * its own constructor, and throwing when the latter was unset. Two things were wrong with that: an
 * open package cannot require configuration it does not ship in order to be constructible, and that
 * namespace never existed anyway (the engine merges its file as `splicewire.composition`), so both
 * reads silently returned nothing. Wiring is now the service provider's job; see
 * {@see \Splicewire\Composition\CompositionDataServiceProvider} and `config/composition-angles.php`.
 *
 * The neutral base is OPTIONAL. Absent one, a compose that matches nothing returns an empty shape
 * flagged `neutralBase` rather than raising — the flag is what consumers branch on.
 *
 * Which angles survive is no longer decided here. It is {@see SteeringResolver}'s one axis-keyed rule,
 * shared with music: contributions layer across axes and override within an axis, declaration order
 * deciding. Text is the DEGENERATE case of that rule — it buckets by the angle's own identity, so two
 * contributions never collide and the override never visibly fires. What used to be a bare
 * `isset($contributors[$key])` skip was always that override; it simply had nothing to override.
 *
 * The composer keeps every behaviour it had, including the merge — union sections, grounding emphasis
 * and components; intent and title guidance from the primary; fragments joined. That merge is text's
 * payload knowledge and stays text's: the mechanism settles WHICH contributions survive, the medium
 * settles HOW survivors merge.
 */
class AngleComposer
{
    /** @var array<string, AngleContributor> */
    private array $registry = [];

    /**
     * @param  iterable<AngleContributor>  $contributors  the registered angle vocabulary
     * @param  AngleContributor|null  $neutralBase  the shape used when no angle applies; null is a
     *                                              supported state, not a misconfiguration
     */
    public function __construct(iterable $contributors = [], private ?AngleContributor $neutralBase = null)
    {
        foreach ($contributors as $contributor) {
            $this->registry[$contributor->angle()->value()] = $contributor;
        }
    }

    /**
     * @param  array<int, AngleContract|string>  $angles  ordered, primary first
     */
    public function compose(array $angles): ComposedShape
    {
        $declared = [];

        foreach ($angles as $angle) {
            $key = $angle instanceof AngleContract ? $angle->value() : $angle;

            if (! is_string($key)) {
                continue;
            }

            // The fallback stays addressable BY KEY even though it is no longer pre-registered, so
            // layering it onto a real angle still merges its sections and fragment. Dropping that would
            // have been a silent content change that the neutrality tests could not see.
            $contributor = $this->registry[$key] ?? null;

            if ($contributor === null && $this->neutralBase?->angle()->value() === $key) {
                $contributor = $this->neutralBase;
            }

            if ($contributor !== null) {
                $declared[] = $contributor;
            }
        }

        $resolved = (new SteeringResolver)->resolve($this->contributions($declared, $contributorOf));

        if ($resolved->all() === []) {
            if ($this->neutralBase === null) {
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
                    steering: $resolved->fingerprint(),
                );
            }

            $resolved = (new SteeringResolver)->resolve(
                $this->contributions([$this->neutralBase], $contributorOf),
            );
        }

        $survivors = $resolved->all();

        // The shape is neutral when it resolved to the neutral fallback contributor ALONE — whether
        // because no requested angle matched the registry, or because the caller requested the fallback
        // angle explicitly. Either way the piece carries no real editorial angle. Recorded here because
        // the fallback contributor is not empty (it carries sections and a prompt fragment), so
        // neutrality cannot be derived downstream from emptiness — it must be stamped at composition time.
        // Keyed off the INJECTED neutral base, so the flag holds whether it was reached through the
        // registry (a host that also lists it among contributors) or through the neutral path.
        $neutralBase = $this->neutralBase !== null
            && array_map(fn (SteeringContribution $c) => $c->axis, $survivors)
                === [$this->neutralBase->angle()->value()];

        return $this->merge(
            array_map(fn (SteeringContribution $c) => $contributorOf[spl_object_id($c)], $survivors),
            $resolved->fingerprint(),
            $neutralBase,
        );
    }

    /**
     * The requested contributors as generic contributions, plus a map back to the contributor that
     * declared each one — by object identity, since the composer needs the contributor itself to merge
     * and the mechanism only hands back contributions.
     *
     * Axis and key are both the angle's own identity. That is the whole configuration text needs: it is
     * what makes collisions impossible here and overrides routine in music.
     *
     * @param  array<int, AngleContributor>  $contributors
     * @param  array<int, AngleContributor>|null  $contributorOf  out-param, keyed by contribution id
     * @return array<int, SteeringContribution>
     */
    private function contributions(array $contributors, ?array &$contributorOf): array
    {
        $contributorOf ??= [];
        $contributions = [];

        foreach ($contributors as $contributor) {
            $contribution = new SteeringContribution(
                axis: $contributor->angle()->value(),
                key: $contributor->angle()->value(),
                // Text's payload. The mechanism never opens it; it is here so the fingerprint's hash
                // covers what the angle actually CONTRIBUTES — rewrite a section plan or a prompt
                // fragment without renaming the angle and the fingerprint must move.
                payload: [
                    'intent' => $contributor->intent()?->value(),
                    'sections' => $contributor->sections(),
                    'groundingEmphasis' => array_map(
                        fn ($category) => $category->value(),
                        $contributor->groundingEmphasis(),
                    ),
                    'encouragedComponents' => array_map(
                        fn ($component) => $component->value(),
                        $contributor->encouragedComponents(),
                    ),
                    'promptFragment' => $contributor->promptFragment(),
                    'titleGuidance' => $contributor->titleGuidance(),
                ],
            );

            $contributions[] = $contribution;
            $contributorOf[spl_object_id($contribution)] = $contributor;
        }

        return $contributions;
    }

    /**
     * @param  array<int, AngleContributor>  $contributors  primary first
     * @param  array<string, mixed>  $steering  the resolved fingerprint, stamped for attribution
     * @param  bool  $neutralBase  true when $contributors is the neutral fallback alone
     */
    private function merge(array $contributors, array $steering, bool $neutralBase = false): ComposedShape
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
            steering: $steering,
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
