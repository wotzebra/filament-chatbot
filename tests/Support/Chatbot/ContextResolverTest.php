<?php

use Illuminate\Http\Request;
use Wotz\FilamentChatbot\Support\Chatbot\ContextResolver;

it('returns null when the raw context is empty', function () {
    $request = Request::create('/');
    $request->headers->set('Referer', 'https://example.test/admin/orders/42');

    expect((new ContextResolver)([], $request))->toBeNull();
});

it('includes the referer url as page metadata when a record is given', function () {
    $request = Request::create('/');
    $request->headers->set('Referer', 'https://example.test/admin/orders/42');

    $resolved = (new ContextResolver)(['type' => 'order'], $request);

    expect($resolved)
        ->toContain('Current page context:')
        ->toContain('"url": "https://example.test/admin/orders/42"');
});

it('serializes the raw context as json under the record key', function () {
    $resolved = (new ContextResolver)(
        ['type' => 'order', 'id' => 42, 'number' => 'O-42'],
        Request::create('/'),
    );

    expect($resolved)
        ->toContain('Current page context:')
        ->toContain('"type": "order"')
        ->toContain('"id": 42')
        ->toContain('"number": "O-42"');
});

it('includes the current panel id as page metadata', function () {
    $resolved = (new ContextResolver)(
        ['type' => 'order'],
        Request::create('/'),
    );

    expect($resolved)->toContain('"panel": "test"');
});
