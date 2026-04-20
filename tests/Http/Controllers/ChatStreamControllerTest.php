<?php

use Illuminate\Support\Facades\Context;
use Wotz\FilamentChatbot\Agents\Assistant;
use Wotz\FilamentChatbot\Models\AgentConversation;

beforeEach(function () {
    Assistant::fake(['Hello from the AI!']);

    $this->user = $this->makeTestUser();

    Context::forgetHidden('chatbot.context');
});

it('returns 401 for unauthenticated requests', function () {
    $this->postJson(route('chatbot.stream'), [
        'message' => fake()->sentence(),
        'conversation_id' => fake()->uuid(),
    ])->assertUnauthorized();
});

it('streams a response for an authenticated request', function () {
    $conversation = AgentConversation::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->post(route('chatbot.stream'), [
            'message' => fake()->sentence(),
            'conversation_id' => $conversation->id,
        ])->assertOk();
});

it('forwards the user message to the agent', function () {
    $conversation = AgentConversation::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->post(route('chatbot.stream'), [
            'message' => $message = fake()->sentence(),
            'conversation_id' => $conversation->id,
        ]);

    Assistant::assertPrompted($message);
});

it('trims whitespace from the user message before forwarding it to the agent', function () {
    $conversation = AgentConversation::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->post(route('chatbot.stream'), [
            'message' => '  ' . ($message = fake()->sentence()) . '  ',
            'conversation_id' => $conversation->id,
        ]);

    Assistant::assertPrompted($message);
});

it('returns 403 when the conversation belongs to another user', function () {
    $conversation = AgentConversation::factory()->create(['user_id' => 999]);

    $this->actingAs($this->user)
        ->postJson(route('chatbot.stream'), [
            'message' => fake()->sentence(),
            'conversation_id' => $conversation->id,
        ])->assertForbidden();
});

it('returns 403 when the conversation does not exist', function () {
    $this->actingAs($this->user)
        ->postJson(route('chatbot.stream'), [
            'message' => fake()->sentence(),
            'conversation_id' => fake()->uuid(),
        ])->assertForbidden();
});

it('returns 422 when a required field is missing', function (array $payload) {
    $this->actingAs($this->user)
        ->postJson(route('chatbot.stream'), $payload)
        ->assertUnprocessable();
})->with([
    'missing message' => [['conversation_id' => '8e1a1f8d-1a2b-4c3d-9e4f-5a6b7c8d9e0f']],
    'missing conversation_id' => [['message' => 'hello']],
]);

it('returns 422 when the context is not an array', function () {
    $conversation = AgentConversation::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->postJson(route('chatbot.stream'), [
            'message' => fake()->sentence(),
            'conversation_id' => $conversation->id,
            'context' => 'not-an-array',
        ])->assertUnprocessable();
});

it('resolves context via the plugin resolver and stores it in Laravel Context', function () {
    $conversation = AgentConversation::factory()->create(['user_id' => $this->user->id]);

    $this->chatbotPlugin->contextResolver(
        fn (array $context) => $context === [] ? null : 'resolved: ' . json_encode($context),
    );

    $this->actingAs($this->user)
        ->post(route('chatbot.stream'), [
            'message' => fake()->sentence(),
            'conversation_id' => $conversation->id,
            'context' => ['type' => 'order', 'id' => 42],
        ]);

    expect(Context::getHidden('chatbot.context'))
        ->toBe('resolved: {"type":"order","id":42}');
});

it('stores null in Laravel Context when no context is sent', function () {
    $conversation = AgentConversation::factory()->create(['user_id' => $this->user->id]);

    Context::addHidden('chatbot.context', 'stale');

    $this->actingAs($this->user)
        ->post(route('chatbot.stream'), [
            'message' => fake()->sentence(),
            'conversation_id' => $conversation->id,
        ]);

    expect(Context::getHidden('chatbot.context'))->toBeNull();
});
