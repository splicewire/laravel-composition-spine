<?php

use Splicewire\Composition\NodeActions\NodeActionCandidateData;
use Splicewire\Composition\NodeActions\NodeActionCandidateInputData;
use Splicewire\Composition\NodeActions\NodeActionCandidateProducer;
use Splicewire\Composition\NodeActions\NodeActionMode;

it('carries a caller-selected truth schema across the neutral producer seam without model routing', function () {
    $input = NodeActionCandidateInputData::from([
        'mode' => 'refine',
        'before' => ['address' => 'block-7', 'truth' => ['node' => ['text' => 'Original']], 'standing' => []],
        'outputSchema' => ['type' => 'object', 'properties' => ['node' => ['type' => 'object']]],
        'instruction' => 'Tighten this sentence.',
        'context' => ['allowedKinds' => ['text']],
    ]);
    $producer = new class implements NodeActionCandidateProducer
    {
        public function produce(NodeActionCandidateInputData $input): NodeActionCandidateData
        {
            return new NodeActionCandidateData(['node' => ['text' => 'Tighter.']]);
        }
    };

    expect($input->mode)->toBe(NodeActionMode::Refine)
        ->and($input->before->address)->toBe('block-7')
        ->and($producer->produce($input)->truth)->toBe(['node' => ['text' => 'Tighter.']])
        ->and(array_keys($input->toArray()))->not->toContain('model', 'provider');
});
