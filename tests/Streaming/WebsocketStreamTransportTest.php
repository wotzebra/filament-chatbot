<?php

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Wotz\FilamentChatbot\Agents\Assistant;
use Wotz\FilamentChatbot\Broadcasting\ChatbotStreamEvent;
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

it('returns a 202 JSON acknowledgement instead of an SSE stream', function () {
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

it('broadcasts a ChatbotStreamEvent on the private conversation channel when the job runs', function () {
    Event::fake([ChatbotStreamEvent::class]);

    $conversation = AgentConversation::factory()->create(['user_id' => $this->user->id]);

    (new StreamAgentResponseJob(
        conversationId: $conversation->id,
        message: fake()->sentence(),
        userId: $this->user->id,
        userModel: null,
    ))->handle();

    Event::assertDispatched(ChatbotStreamEvent::class, function (ChatbotStreamEvent $event) use ($conversation): bool {
        $channel = $event->broadcastOn();

        return $event->conversationId === $conversation->id
            && $channel instanceof PrivateChannel
            && $channel->name === 'private-chatbot.conversation.' . $conversation->id;
    });
});

it('emits a final done event after the stream completes', function () {
    Event::fake([ChatbotStreamEvent::class]);

    $conversation = AgentConversation::factory()->create(['user_id' => $this->user->id]);

    (new StreamAgentResponseJob(
        conversationId: $conversation->id,
        message: fake()->sentence(),
        userId: $this->user->id,
        userModel: null,
    ))->handle();

    Event::assertDispatched(
        ChatbotStreamEvent::class,
        fn (ChatbotStreamEvent $event): bool => $event->type === 'done',
    );
});

it('broadcasts a friendly error event when the agent throws', function () {
    Assistant::fake(fn () => throw new RuntimeException('boom'));
    Event::fake([ChatbotStreamEvent::class]);

    $conversation = AgentConversation::factory()->create(['user_id' => $this->user->id]);

    (new StreamAgentResponseJob(
        conversationId: $conversation->id,
        message: fake()->sentence(),
        userId: $this->user->id,
        userModel: null,
    ))->handle();

    Event::assertDispatched(
        ChatbotStreamEvent::class,
        fn (ChatbotStreamEvent $event): bool => $event->type === 'text_delta',
    );

    Event::assertDispatched(
        ChatbotStreamEvent::class,
        fn (ChatbotStreamEvent $event): bool => $event->type === 'done',
    );
});
