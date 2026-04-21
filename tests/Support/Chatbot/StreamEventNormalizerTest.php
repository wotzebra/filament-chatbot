<?php

use Wotz\FilamentChatbot\Support\Chatbot\StreamEventNormalizer;

it('normalizes cumulative text snapshots into incremental deltas', function () {
    $normalizer = new StreamEventNormalizer;

    $first = $normalizer->normalize('', [
        'type' => 'text_delta',
        'delta' => 'KALK',
    ]);

    $second = $normalizer->normalize($first['message'], [
        'type' => 'text_delta',
        'delta' => 'KALK 1: Kalkhennep',
    ]);

    expect($first['event']['delta'])->toBe('KALK')
        ->and($first['message'])->toBe('KALK')
        ->and($second['event']['delta'])->toBe(' 1: Kalkhennep')
        ->and($second['message'])->toBe('KALK 1: Kalkhennep');
});

it('keeps incremental text deltas unchanged', function () {
    $normalizer = new StreamEventNormalizer;

    $normalized = $normalizer->normalize('Hallo', [
        'type' => 'text_delta',
        'delta' => ' wereld',
    ]);

    expect($normalized['event']['delta'])->toBe(' wereld')
        ->and($normalized['message'])->toBe('Hallo wereld');
});

it('preserves explicit full message events', function () {
    $normalizer = new StreamEventNormalizer;

    $normalized = $normalizer->normalize('oude tekst', [
        'type' => 'text_end',
        'message' => 'nieuwe tekst',
    ]);

    expect($normalized['event']['message'])->toBe('nieuwe tekst')
        ->and($normalized['message'])->toBe('nieuwe tekst');
});
