<?php

namespace Splicewire\Composition\Schema;

use ReflectionClass;
use Schemastud\DataSchemas\Generators\Generator;
use Splicewire\Composition\GenerationAttributesStrategy;

/**
 * Projects a NESTED, model-decided-structure generation schema from a fat root `Data`
 * graph — the recursive sibling of the flat {@see BeatGrammar} (which fans one leaf into
 * a list of FIXED sibling beat nodes).
 *
 * Where `BeatGrammar` decides the beats host-side (a fixed list of intro/story/cast
 * sections), `ArticleGrammar` lets the MODEL decide the structure: the root graph carries
 * a single generated `outline` object beat whose own generated field (a nested array beat,
 * e.g. `sections`) fans once per entry the model emitted. The engine's recursive child-spec
 * drain (`Splicewire\Composition\Generation\TreeDrain::childSpecs()`) reads the
 * parent node's generated field of the nested beat's name, so the count is whatever the
 * model returned for the outline — no host plan, no `DocumentPlan` (an inline schema
 * hydrates into a generic `SchemaNode`, never a `DocumentPlan`, so the sibling
 * `outline + sections` shape would fan ZERO sections; the nested shape is the inline path).
 *
 * Keyword emission is NOT re-implemented here: the registered
 * {@see GenerationAttributesStrategy} projects the
 * `#[Beat]/#[Generate]/#[Ground]/#[Prose]/#[Pause]` attributes on the `Data` graph to the
 * `x-*` keywords during {@see Generator::generate()}. `ArticleGrammar` only runs
 * the generator and hardens the result for strict structured output (exactly like
 * {@see BeatGrammar::build()}): it strips `examples` and stamps `additionalProperties:false`
 * + `required` = all property keys on every object node (root, nested beat nodes, and the
 * `items` element of every array).
 *
 * Hard rule (shared with `BeatGrammar`): the typed `Data` graph is the SINGLE authored
 * artifact — there is no second hand-written generation-schema array.
 */
class ArticleGrammar
{
    private ?string $title = null;

    /**
     * @param  class-string  $rootClass
     */
    private function __construct(
        private string $rootClass,
    ) {}

    /**
     * @param  class-string  $rootClass  the root `Data` graph carrying the nested beat attributes.
     */
    public static function for(string $rootClass): self
    {
        return new self($rootClass);
    }

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Project + harden the nested generation schema.
     *
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $reflection = new ReflectionClass($this->rootClass);

        // The host's configured generator, not a bare one — a bare JsonSchemaGenerator reads no
        // config, so `schema_metadata`/`schema_version`/`base_uri` vanish and a versioned root
        // class throws MissingSchemaBaseUri naming a key the host already set.
        // No canGenerate() guard: this IS build(), and a grammar that cannot project its root
        // class has no useful degraded result — the chain's throw names the class and the
        // configured generators, which is strictly better than returning an empty schema.
        $schema = app(Generator::class)->generate($reflection);

        // The generator hoists nested `Data` classes into `$defs` and points at them with
        // `#/$defs/Short` refs. The engine's GrammarWalk only resolves a self-`$ref` (`#`), so an
        // antenna schema must be fully INLINED — the nested `outline`/`sections` object tree drains
        // exactly like RecursiveAntennaTest::nestedAntennaSchema(). Dereference every `$defs` ref in
        // place, then drop the now-unused `$defs` bag.
        $defs = is_array($schema['$defs'] ?? null) ? $schema['$defs'] : [];
        $schema = $this->inlineRefs($schema, $defs);
        unset($schema['$defs']);

        $schema = $this->harden($schema);

        $schema['type'] = 'object';
        $schema['title'] = $this->title ?? $reflection->getShortName();

        return $schema;
    }

    /**
     * Inline every `#/$defs/Short` `$ref` against the hoisted `$defs` bag so the schema is a single
     * self-contained nested object tree (no `$ref`/`$defs`). Handles three shapes the generator
     * emits for a nested `Data` node: a bare `{$ref, ...siblings}` (a nullable nested object keeps
     * `$ref` + `nullable` + `description` + x-* siblings), an `anyOf:[{$ref}, {type:null}]` nullable
     * wrapper, and an array's `items: {$ref}`. The referenced def's body replaces the ref while any
     * sibling keywords (description, x-beat/x-generate/x-ground, nullable) are preserved on top.
     *
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $defs
     * @return array<string, mixed>
     */
    private function inlineRefs(array $schema, array $defs): array
    {
        // Collapse an `anyOf:[{$ref}, {type:null}]` nullable wrapper to its single object ref before
        // resolving, so the inlined node is the object itself (the engine has no anyOf handling).
        if (isset($schema['anyOf']) && is_array($schema['anyOf'])) {
            foreach ($schema['anyOf'] as $member) {
                if (is_array($member) && isset($member['$ref'])) {
                    $siblings = $schema;
                    unset($siblings['anyOf']);

                    return $this->inlineRefs(['$ref' => $member['$ref']] + $siblings, $defs);
                }
            }
        }

        if (isset($schema['$ref']) && is_string($schema['$ref'])) {
            $resolved = $this->resolveDef($schema['$ref'], $defs);

            $siblings = $schema;
            unset($siblings['$ref'], $siblings['nullable']);

            // Sibling keywords (x-beat/x-generate/x-ground, description) win over the def's own.
            $schema = $this->inlineRefs($resolved, $defs);
            $schema = $siblings + $schema;
        }

        if (isset($schema['properties']) && is_array($schema['properties'])) {
            $properties = [];
            foreach ($schema['properties'] as $key => $value) {
                $properties[$key] = is_array($value) ? $this->inlineRefs($value, $defs) : $value;
            }
            $schema['properties'] = $properties;
        }

        if (isset($schema['items']) && is_array($schema['items'])) {
            $schema['items'] = $this->inlineRefs($schema['items'], $defs);
        }

        return $schema;
    }

    /**
     * Resolve a `#/$defs/Short` ref to its def body. A non-local or unknown ref resolves to an empty
     * object (defensive — the engine never sees a dangling ref).
     *
     * @param  array<string, mixed>  $defs
     * @return array<string, mixed>
     */
    private function resolveDef(string $ref, array $defs): array
    {
        $prefix = '#/$defs/';

        if (! str_starts_with($ref, $prefix)) {
            return [];
        }

        $key = substr($ref, strlen($prefix));

        return is_array($defs[$key] ?? null) ? $defs[$key] : [];
    }

    /**
     * Recursively harden a schema array for strict structured output: drop every `examples`
     * keyword (strict providers reject it) and, for every object schema with `properties`,
     * set `additionalProperties:false` and `required` to all its property keys. Recurses into
     * each property, into an array's `items` element, and into nested beat nodes so the whole
     * tree (root → outline → sections → item) satisfies the strict contract.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function harden(array $schema): array
    {
        unset($schema['examples']);

        if (isset($schema['properties']) && is_array($schema['properties'])) {
            $properties = [];
            foreach ($schema['properties'] as $key => $value) {
                $properties[$key] = is_array($value) ? $this->harden($value) : $value;
            }

            $schema['properties'] = $properties;
            $schema['additionalProperties'] = false;
            $schema['required'] = array_keys($properties);
        }

        if (isset($schema['items']) && is_array($schema['items'])) {
            $schema['items'] = $this->harden($schema['items']);
        }

        return $schema;
    }
}
