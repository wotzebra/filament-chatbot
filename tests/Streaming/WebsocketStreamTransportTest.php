<?php

use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Wotz\FilamentChatbot\Agents\Assistant;
use Wotz\FilamentChatbot\Jobs\StreamAgentResponseJob;
use Wotz\FilamentChatbot\Models\AgentConversation;
use Wotz\FilamentChatbot\Streaming\TransportManager;

beforeEach(function () {
    config()->set('filament-chatbot.stream.transport', 'websocket');
    config()->set('filament-chatbot.stream.websocket.channel_prefix', 'chatbot.conversation');

    // Manager is a singleton; forget the cached instance so the websocket
    // driver is resolved fresh for this test's config override.
    app()->forgetInstance(TransportManager::class);

    Assistant::fake(['Hello from the AI!']);

    $this->user = $this->makeTestUser();

    Context::forgetHidden('chatbot.context');
});

it('returns a 202 JSON acknowledgement with the conversation channel', function () {
    Queue::fake();

    $conversation = AgentConversation::factory()->create(['user_id' => $this->user->id]);

    $response = $this->actingAs($this->user)
        ->postJson(route('chatbot.stream'), [
            'message' => fake()->sentence(),
            'conversation_id' => $conversation->id,
        ]);

    $response->assertStatus(202)
        ->assertJson([
            'accepted' => true,
            'conversation_id' => $conversation->id,
            'transport' => 'websocket',
        ]);

    expect($response->json('client.channel'))
        ->toBe('chatbot.conversation.' . $conversation->id);
});

it('dispatches the StreamAgentResponseJob with the conversation, message, and user', function () {
    Queue::fake();

    $conversation = AgentConversation::factory()->create(['user_id' => $this->user->id]);
    $message = fake()->sentence();

    $this->actingAs($this->user)
        ->postJson(route('chatbot.stream'), [
            'message' => $message,
            'conversation_id' => $conversation->id,
        ]);

    Queue::assertPushed(
        StreamAgentResponseJob::class,
        fn (StreamAgentResponseJob $job): bool => $job->conversationId === $conversation->id
            && $job->message === $message
            && $job->userId === $this->user->id,
    );
});

it('does not dispatch a duplicate StreamAgentResponseJob when the same stream is already active in the session', function () {
    Queue::fake();
    Log::spy();

    $conversation = AgentConversation::factory()->create(['user_id' => $this->user->id]);
    $message = fake()->sentence();

    session()->put(filament('chatbot')->getConversationKey() . '_active_streams', [
        $conversation->id => ['message' => $message],
    ]);

    $this->actingAs($this->user)
        ->postJson(route('chatbot.stream'), [
            'message' => $message,
            'conversation_id' => $conversation->id,
        ])->assertStatus(202);

    Queue::assertNotPushed(StreamAgentResponseJob::class);

    Log::shouldHaveReceived('debug')
        ->withArgs(fn (string $logMessage, array $context): bool => $logMessage === 'filament-chatbot.websocket.start'
            && $context['conversation_id'] === $conversation->id
            && $context['duplicate_skipped'] === true)
        ->once();
});

it('broadcasts streamed SDK events on the private conversation channel when the job runs', function () {
    config()->set('broadcasting.default', 'log');
    Log::spy();

    $conversation = AgentConversation::factory()->create(['user_id' => $this->user->id]);

    (new StreamAgentResponseJob(
        conversationId: $conversation->id,
        message: fake()->sentence(),
        userId: $this->user->id,
        userModel: null,
    ))->handle();

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $message): bool => str_contains($message, 'text_delta')
            && str_contains($message, 'private-chatbot.conversation.' . $conversation->id))
        ->atLeast()
        ->once();
});

it('broadcasts a friendly error event when the agent throws', function () {
    Assistant::fake(fn () => throw new RuntimeException('boom'));

    config()->set('broadcasting.default', 'log');
    Log::spy();

    $conversation = AgentConversation::factory()->create(['user_id' => $this->user->id]);

    (new StreamAgentResponseJob(
        conversationId: $conversation->id,
        message: fake()->sentence(),
        userId: $this->user->id,
        userModel: null,
    ))->handle();

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $message): bool => str_contains($message, 'text_delta')
            && str_contains($message, 'private-chatbot.conversation.' . $conversation->id))
        ->atLeast()
        ->once();

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $message): bool => str_contains($message, 'stream_end')
            && str_contains($message, 'private-chatbot.conversation.' . $conversation->id))
        ->atLeast()
        ->once();
});
