<?php

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Broadcast;
use Wotz\FilamentChatbot\Models\AgentConversation;

beforeEach(function () {
    config()->set('filament-chatbot.stream.transport', 'websocket');
    config()->set('filament-chatbot.stream.websocket.channel_prefix', 'chatbot.conversation');

    // The service provider only loads the channels file when transport=websocket
    // at boot time, which happens before the test's config override. Register
    // the channel definitions explicitly here.
    require __DIR__ . '/../../routes/channels.php';
});

function chatbotChannelCallback(): ?Closure
{
    $pattern = 'chatbot.conversation.{conversationId}';

    return Broadcast::driver()->getChannels()->get($pattern);
}

it('authorizes the conversation owner on the private channel', function () {
    $owner = $this->makeTestUser();
    $conversation = AgentConversation::factory()->create(['user_id' => $owner->id]);

    $callback = chatbotChannelCallback();

    expect($callback)->not->toBeNull();

    expect($callback($owner, $conversation->id))->toBeTrue();
});

it('rejects a user that does not own the conversation', function () {
    $owner = $this->makeTestUser();
    $conversation = AgentConversation::factory()->create(['user_id' => $owner->id]);

    $stranger = new User;
    $stranger->id = 999;
    $stranger->name = 'Other User';

    $callback = chatbotChannelCallback();

    expect($callback)->not->toBeNull();

    expect($callback($stranger, $conversation->id))->toBeFalse();
});

it('rejects access to a non-existent conversation', function () {
    $owner = $this->makeTestUser();

    $callback = chatbotChannelCallback();

    expect($callback)->not->toBeNull();

    expect($callback($owner, fake()->uuid()))->toBeFalse();
});
