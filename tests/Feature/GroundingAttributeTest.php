<?php

use Schemastud\DataSchemas\Generators\JsonSchemaGenerator;
use Spatie\LaravelData\Data;
use Splicewire\Grounding\Attributes\Grounding;
use Splicewire\Composition\GenerationAttributesStrategy;
use Splicewire\Composition\KeywordVocabulary;

/**
 * Grounding-fusion issue 03 — the `Grounding` attribute projects declared grounding SOURCES to the
 * `x-splice-grounding` keyword through the shared bindings (mirrors the class-level beat-grammar
 * projection guard): class level for node/root declarations, property level for beat supplements.
 */
#[Grounding(sources: [['context_scope' => 'brand-voice'], ['webhook' => ['endpoint' => 'https://example.test/catalog']]])]
class RootGroundingFixture extends Data
{
    public function __construct(
        public string $body = '',
    ) {}
}

#[Grounding(sources: [['facts' => ['notes' => []]]], fusion: 'reconcile')]
class NamedFusionFixture extends Data
{
    public function __construct(
        public string $body = '',
    ) {}
}

#[Grounding(sources: [['context_scope' => 'compliance']], only: ['compliance'])]
class PinnedGroundingFixture extends Data
{
    public function __construct(
        public string $body = '',
    ) {}
}

#[Grounding(sources: [['facts' => ['notes' => []]]], fusion: 'reconcile', only: ['compliance', 'legal'])]
class PinnedFusionFixture extends Data
{
    public function __construct(
        public string $body = '',
    ) {}
}

class SupplementGroundingFixture extends Data
{
    public function __construct(
        #[Grounding(sources: [['context_scope' => ['query' => 'background']]])]
        public array $sections = [],
    ) {}
}

it('projects a class-level Grounding declaration to x-splice-grounding through the shared class-keyword path', function () {
    $vocab = KeywordVocabulary::shared();
    $keywords = GenerationAttributesStrategy::classKeywords(new ReflectionClass(RootGroundingFixture::class));

    expect($keywords[$vocab->grounding()])->toBe([
        ['context_scope' => 'brand-voice'],
        ['webhook' => ['endpoint' => 'https://example.test/catalog']],
    ]);
});

it('projects the {fusion, sources} object form when a strategy is named — single-sourced through keyword()', function () {
    $vocab = KeywordVocabulary::shared();
    $keywords = GenerationAttributesStrategy::classKeywords(new ReflectionClass(NamedFusionFixture::class));

    expect($keywords[$vocab->grounding()])->toBe(
        (new Grounding(sources: [['facts' => ['notes' => []]]], fusion: 'reconcile'))->keyword(),
    )->and($keywords[$vocab->grounding()]['fusion'])->toBe('reconcile');
});

it('projects the {only, sources} object form when a beat pins named groups — single-sourced through keyword()', function () {
    $vocab = KeywordVocabulary::shared();
    $keywords = GenerationAttributesStrategy::classKeywords(new ReflectionClass(PinnedGroundingFixture::class));

    expect($keywords[$vocab->grounding()])->toBe([
        'only' => ['compliance'],
        'sources' => [['context_scope' => 'compliance']],
    ])->and($keywords[$vocab->grounding()])->toBe(
        (new Grounding(sources: [['context_scope' => 'compliance']], only: ['compliance']))->keyword(),
    );
});

it('projects the {fusion, only, sources} object form when a beat both pins and names a strategy', function () {
    $vocab = KeywordVocabulary::shared();
    $keywords = GenerationAttributesStrategy::classKeywords(new ReflectionClass(PinnedFusionFixture::class));

    expect($keywords[$vocab->grounding()])->toBe([
        'fusion' => 'reconcile',
        'only' => ['compliance', 'legal'],
        'sources' => [['facts' => ['notes' => []]]],
    ]);
});

it('keeps the bare source list when neither only nor fusion is declared — unchanged', function () {
    expect((new Grounding(sources: [['context_scope' => 'brand-voice']]))->keyword())
        ->toBe([['context_scope' => 'brand-voice']]);
});

it('keeps the {fusion, sources} object form (no only key) when only fusion is declared — unchanged', function () {
    expect((new Grounding(sources: [['facts' => ['x' => 1]]], fusion: 'reconcile'))->keyword())
        ->toBe(['fusion' => 'reconcile', 'sources' => [['facts' => ['x' => 1]]]]);
});

it('projects a property-level Grounding supplement onto the field schema', function () {
    $vocab = KeywordVocabulary::shared();
    $schema = (new JsonSchemaGenerator)->generate(new ReflectionClass(SupplementGroundingFixture::class));

    expect($schema['properties']['sections'][$vocab->grounding()])
        ->toBe([['context_scope' => ['query' => 'background']]]);
});

it('names the keyword through the vocabulary (prefix-agnostic)', function () {
    expect(KeywordVocabulary::shared()->grounding())->toBe('x-'.KeywordVocabulary::shared()->prefix.'-grounding')
        ->and((new KeywordVocabulary('alt'))->grounding())->toBe('x-alt-grounding');
});
