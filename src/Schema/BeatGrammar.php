<?php

namespace Splicewire\Composition\Schema;

use ReflectionClass;
use Schemastud\DataSchemas\Generators\Generator;
use Splicewire\Composition\Attributes\Beat;
use Splicewire\Composition\Attributes\Generate;
use Splicewire\Composition\Attributes\Prose;
use Splicewire\Composition\GenerationAttributesStrategy;
use Splicewire\Composition\KeywordVocabulary;
use Splicewire\Grounding\Attributes\Ground;

/**
 * Projects a chart-dependent generation schema from a fat leaf `Data` class plus a
 * dynamic beat list — the attribute-driven replacement for hand-rolled
 * `x-beat`/`x-generate`/`x-ground` array literals (numero triplicated those across
 * Person/Cycle/Daily).
 *
 * The leaf class declares the INVARIANT shape via the shared composition attributes:
 *  - class `#[Beat(BeatKind::Expandable)]` → each beat node's `x-beat` mode;
 *  - class `#[Generate]` / `#[Ground]` → `x-generate: true` / `x-ground: true` per node;
 *  - a property `#[Prose(ProseRole::Subject)]` → the generated prose field (`x-prose`).
 *
 * The per-beat VARYING parts (which beats exist, their descriptions, the prose hint)
 * come from {@see self::beat()} — that is the data-dependent structure a static profile
 * can't express. The node field skeleton itself is emitted from the leaf via
 * {@see Generator}, so it stays in sync with the Data class the result is
 * hydrated back into.
 *
 * `BeatGrammar` is the flat projector: it fans one leaf into a list of sibling beat
 * nodes. A nested/recursive shape (an outline that fans out a variable number of
 * sections) is a different projector built on the same attribute vocabulary.
 *
 * Hard rule this encodes: the fat `Data` class is the SINGLE authored artifact. There
 * is no second hand-written generation-schema array — "reduced" projections come from
 * laravel-data `Optional`/`Lazy`/`only()` and the generator's request/response/llm modes.
 */
class BeatGrammar
{
    /** @var array<int, array{key: string, description: string, prose: ?string}> */
    private array $beats = [];

    private ?string $title = null;

    /**
     * @param  class-string  $leafClass
     */
    private function __construct(
        private string $leafClass,
    ) {}

    /**
     * @param  class-string  $leafClass  the leaf `Data` class carrying the beat attributes.
     */
    public static function for(string $leafClass): self
    {
        return new self($leafClass);
    }

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Add one beat. `$description` is the node-level description; `$prose` overrides the
     * description of the `#[Prose]` field for this beat (e.g. the per-facet
     * interpretation hint).
     */
    public function beat(string $key, string $description, ?string $prose = null): self
    {
        $this->beats[] = ['key' => $key, 'description' => $description, 'prose' => $prose];

        return $this;
    }

    /**
     * Add many beats at once.
     *
     * @param  iterable<array{key: string, description: string, prose?: ?string}>  $beats
     */
    public function beats(iterable $beats): self
    {
        foreach ($beats as $beat) {
            $this->beat($beat['key'], $beat['description'], $beat['prose'] ?? null);
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $reflection = new ReflectionClass($this->leafClass);

        $vocab = KeywordVocabulary::shared();

        $classKeywords = $this->classLevelKeywords($reflection, $vocab);
        [$proseField, $proseRole] = $this->proseField($reflection);

        $leafProperties = $this->stripExamples(
            // Host-configured generator (see ArticleGrammar::build()); a throw is correct for the
            // same reason — a beat grammar with no leaf properties is not a degraded result.
            app(Generator::class)->generate($reflection)['properties'] ?? []
        );

        $properties = [];
        foreach ($this->beats as $beat) {
            $decorated = $this->decorate($leafProperties, $proseField, $proseRole, $beat['prose']);

            $node = ['type' => 'object'] + $classKeywords;
            $node['description'] = $beat['description'];
            // Strict structured-output providers (OpenAI et al.) require every object to set
            // additionalProperties:false and list every property in `required`; the engine forwards
            // each beat node verbatim as the LLM response_format, so the node must satisfy it here.
            $node['additionalProperties'] = false;
            $node['required'] = array_keys($decorated);
            $node['properties'] = $decorated;

            $properties[$beat['key']] = $node;
        }

        return [
            'type' => 'object',
            'title' => $this->title ?? $reflection->getShortName(),
            'properties' => $properties,
        ];
    }

    /**
     * The invariant beat-node keywords the leaf declares at CLASS level — delegated to the shared
     * {@see GenerationAttributesStrategy::classKeywords()} so BeatGrammar nodes, the engine's
     * profile-grammar root, and the property path all project through one binding set.
     *
     * @return array<string, mixed>
     */
    private function classLevelKeywords(ReflectionClass $reflection, KeywordVocabulary $vocab): array
    {
        return GenerationAttributesStrategy::classKeywords($reflection, $vocab);
    }

    /**
     * The first instance of `$attribute` declared at CLASS level, or null.
     *
     * @param  class-string  $attribute
     */
    private function classAttribute(ReflectionClass $reflection, string $attribute): ?object
    {
        $attributes = $reflection->getAttributes($attribute);

        return $attributes === [] ? null : $attributes[0]->newInstance();
    }

    /**
     * The first property marked `#[Prose(...)]` and its role value, or [null, null].
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function proseField(ReflectionClass $reflection): array
    {
        foreach ($reflection->getProperties() as $property) {
            foreach ($property->getAttributes(Prose::class) as $attribute) {
                return [$property->getName(), $attribute->newInstance()->role->value];
            }
        }

        return [null, null];
    }

    /**
     * Stamp the prose field with its role + per-beat description, preserving the leaf's
     * generated key order (original keys, then `x-prose`, then `description`).
     *
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private function decorate(array $properties, ?string $field, ?string $role, ?string $proseDescription): array
    {
        if ($field !== null && isset($properties[$field])) {
            if ($role !== null) {
                $properties[$field][KeywordVocabulary::shared()->prose()] = $role;
            }
            if ($proseDescription !== null) {
                $properties[$field]['description'] = $proseDescription;
            }
        }

        return $properties;
    }

    /**
     * Strip `examples` keywords the (non-strict) generator emits — strict structured-output providers
     * reject them. Recurses so nested object/array shapes are cleaned too.
     *
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private function stripExamples(array $properties): array
    {
        foreach ($properties as $key => $value) {
            if ($key === 'examples') {
                unset($properties[$key]);

                continue;
            }
            if (is_array($value)) {
                $properties[$key] = $this->stripExamples($value);
            }
        }

        return $properties;
    }
}
