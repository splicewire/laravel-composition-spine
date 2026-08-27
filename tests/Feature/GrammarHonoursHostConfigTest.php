<?php

use Schemastud\DataSchemas\Generators\Generator;
use Splicewire\Composition\Schema\ArticleGrammar;
use Splicewire\Composition\Tests\Stubs\ClassLevelBeatData;

/**
 * The grammars used to `new JsonSchemaGenerator` with no argument, which takes NO config: the
 * `strategies` and `id_parsers` keys self-heal off the container, but `schema_metadata`,
 * `schema_version` and `base_uri` do not — so a host that configured `$schema` got documents
 * without one, and a versioned class threw MissingSchemaBaseUri naming a key the host had set.
 *
 * These pin the fix at the seam that matters: the grammar reads the HOST's `data-schemas`
 * config, because it resolves the container-bound Generator instead of constructing one.
 */
it('emits $schema from the host config, which a bare generator could never see', function () {
    config()->set('data-schemas.schema_metadata.$schema', true);
    config()->set('data-schemas.schema_version', 'https://json-schema.org/draft/2020-12/schema');

    $schema = ArticleGrammar::for(ClassLevelBeatData::class)->title('Article')->build();

    expect($schema['$schema'])->toBe('https://json-schema.org/draft/2020-12/schema');
});

it('omits $schema when the host turned the metadata off', function () {
    config()->set('data-schemas.schema_metadata.$schema', false);

    $schema = ArticleGrammar::for(ClassLevelBeatData::class)->title('Article')->build();

    expect($schema)->not->toHaveKey('$schema');
});

it('binds the Generator contract, not the concrete JsonSchemaGenerator, so the chain is swappable', function () {
    expect(app(Generator::class))->toBeInstanceOf(Generator::class);
});
