<?php

use Splicewire\Composition\Wire\ComplianceCheckData;
use Splicewire\Composition\Wire\DeclarationDetailData;
use Splicewire\Composition\Wire\DisclosureData;

/**
 * gap-assessment-build 41: the three Determination wire DTOs give the flagship codegen + connector a typed view
 * over the `api/v1` Determination envelopes, so consumers stop array-poking. Each surfaces the named wire fields
 * as typed properties and keeps the full raw record in `$attributes` (the sanctioned superset seam). These are
 * the shapes the connector DeterminationTest fixtures assert.
 */
it('hydrates a compliance check from its wire record and keeps the raw superset', function () {
    $data = [
        'id' => 'decl-1',
        'business' => 'Acme Botanicals',
        'disclosureId' => 'disc-1',
        'checkedAt' => '2026-11-15T21:00:00Z',
        'standing' => 'clear',
        'changed' => null,
        'recheckedAt' => null,
        'checks' => 3,
        'facts' => ['listings' => ['Vitamin C']],
        'products' => [],
        'claims' => [['claim' => 'cbd.thc_limit']],
        'requirements' => [],
        'gaps' => [],
        'warnings' => [],
        'extra' => 'rides-in-attributes',
    ];

    $dto = ComplianceCheckData::fromArray($data);

    expect($dto->id)->toBe('decl-1')
        ->and($dto->business)->toBe('Acme Botanicals')
        ->and($dto->disclosureId)->toBe('disc-1')
        ->and($dto->checkedAt)->toBe('2026-11-15T21:00:00Z')
        ->and($dto->standing)->toBe('clear')
        ->and($dto->changed)->toBeNull()
        ->and($dto->checks)->toBe(3)
        ->and($dto->facts)->toBe(['listings' => ['Vitamin C']])
        ->and($dto->claims)->toBe([['claim' => 'cbd.thc_limit']])
        ->and($dto->factsRevisionId)->toBeNull()
        ->and($dto->declarationHash)->toBeNull()
        ->and($dto->attributes)->toBe($data)
        ->and($dto->get('extra'))->toBe('rides-in-attributes');
});

it('carries the revise-only facts revision id and declaration hash when present', function () {
    $dto = ComplianceCheckData::fromArray([
        'id' => 'decl-1', 'disclosureId' => 'disc-1', 'standing' => 'gaps',
        'changed' => true, 'checks' => 4,
        'factsRevisionId' => '5d2c7a10-1b3e-4f6a-9c8d-7e6f5a4b3c2d',
        'declarationHash' => str_repeat('e', 64),
    ]);

    expect($dto->changed)->toBeTrue()
        ->and($dto->factsRevisionId)->toBe('5d2c7a10-1b3e-4f6a-9c8d-7e6f5a4b3c2d')
        ->and($dto->declarationHash)->toBe(str_repeat('e', 64));
});

it('hydrates a declaration detail with its disclosure timeline', function () {
    $data = [
        'id' => 'decl-1',
        'label' => 'Acme Botanicals',
        'subVertical' => 'cbd',
        'declarationHash' => str_repeat('a', 64),
        'declaredAt' => '2026-11-01T00:00:00Z',
        'latestStatus' => 'determined',
        'redetermined' => false,
        'declaration' => ['vertical' => 'cbd'],
        'disclosures' => [
            ['id' => 'disc-1', 'asOf' => '2026-11-15T21:00:00Z', 'corpusHash' => str_repeat('b', 64)],
        ],
    ];

    $dto = DeclarationDetailData::fromArray($data);

    expect($dto->id)->toBe('decl-1')
        ->and($dto->label)->toBe('Acme Botanicals')
        ->and($dto->subVertical)->toBe('cbd')
        ->and($dto->declarationHash)->toBe(str_repeat('a', 64))
        ->and($dto->latestStatus)->toBe('determined')
        ->and($dto->redetermined)->toBeFalse()
        ->and($dto->declaration)->toBe(['vertical' => 'cbd'])
        ->and($dto->disclosures[0]['id'])->toBe('disc-1')
        ->and($dto->attributes)->toBe($data);
});

it('hydrates an unenveloped disclosure export', function () {
    $data = [
        'asOf' => '2026-11-15T21:00:00Z',
        'subject' => 'Acme Botanicals',
        'verdicts' => [],
        'awareness' => [['kind' => 'not_examined']],
        'referenceHash' => str_repeat('c', 64),
        'assertionHash' => str_repeat('d', 64),
    ];

    $dto = DisclosureData::fromArray($data);

    expect($dto->asOf)->toBe('2026-11-15T21:00:00Z')
        ->and($dto->subject)->toBe('Acme Botanicals')
        ->and($dto->awareness)->toBe([['kind' => 'not_examined']])
        ->and($dto->referenceHash)->toBe(str_repeat('c', 64))
        ->and($dto->assertionHash)->toBe(str_repeat('d', 64))
        ->and($dto->catalog)->toBe([])
        ->and($dto->attributes)->toBe($data);
});

it('tolerates snake_case keys from the server shape', function () {
    $check = ComplianceCheckData::fromArray([
        'id' => 'd', 'disclosure_id' => 'disc-1', 'checked_at' => 't', 'standing' => 'clear',
        'rechecked_at' => 't2', 'facts_revision_id' => 'rev', 'declaration_hash' => str_repeat('e', 64),
    ]);
    $detail = DeclarationDetailData::fromArray(['id' => 'd', 'sub_vertical' => 'cbd',
        'declaration_hash' => str_repeat('a', 64), 'declared_at' => 't', 'latest_status' => 'determined']);
    $disclosure = DisclosureData::fromArray(['as_of' => 't', 'reference_hash' => str_repeat('c', 64),
        'assertion_hash' => str_repeat('d', 64)]);

    expect($check->disclosureId)->toBe('disc-1')
        ->and($check->checkedAt)->toBe('t')
        ->and($check->recheckedAt)->toBe('t2')
        ->and($check->factsRevisionId)->toBe('rev')
        ->and($detail->subVertical)->toBe('cbd')
        ->and($detail->declaredAt)->toBe('t')
        ->and($detail->latestStatus)->toBe('determined')
        ->and($disclosure->asOf)->toBe('t')
        ->and($disclosure->referenceHash)->toBe(str_repeat('c', 64));
});
