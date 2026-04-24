<?php

use Illuminate\Support\Str;
use Wotz\FilamentChatbot\Services\ChatManager;

it('starts a conversation with a truncated title fallback', function () {
    $conversation = app(ChatManager::class)->start(
        user: $this->makeTestUser(),
        title: str_repeat('A', 120),
    );

    expect($conversation->user_id)->toBe(1)
        ->and($conversation->title)->toBe(Str::limit(str_repeat('A', 120), 80));
});

it('starts a conversation with an empty title when omitted', function () {
    $conversation = app(ChatManager::class)->start(
        user: $this->makeTestUser(),
    );

    expect($conversation->title)->toBe('');
});
