<?php

use Splicewire\Composition\Wire\IntakeEntity;

it('hydrates the camelCase intake entity response', function () {
    $entity = IntakeEntity::fromArray([
        'id' => 'entity_1',
        'name' => 'Acme LLC',
        'definitionRef' => 'merchant.v1',
        'siloId' => 'silo_1',
    ]);

    expect($entity->definitionRef)->toBe('merchant.v1')
        ->and($entity->siloId)->toBe('silo_1');
});
