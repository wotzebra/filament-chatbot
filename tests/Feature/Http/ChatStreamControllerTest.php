<?php

use Wotz\FilamentChatbot\Agents\Assistant;
use Wotz\FilamentChatbot\Models\AgentConversation;

beforeEach(function () {
    Assistant::fake(['Hello from the AI!']);
});

it('returns 401 for unauthenticated requests', function () {
    $this->postJson(route('chatbot.stream'), [
        'message' => fake()->sentence(),
        'conversation_id' => fake()->uuid(),
    ])->assertUnauthorized();
});

it('returns a streaming response for an authenticated request', function () {
    $user = $this->makeTestUser();
    $conversation = AgentConversation::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('chatbot.stream'), [
            'message' => fake()->sentence(),
            'conversation_id' => $conversation->id,
        ])->assertOk();
});

it('records the prompt sent to the agent', function () {
    $user = $this->makeTestUser();
    $conversation = AgentConversation::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('chatbot.stream'), [
            'message' => $message = fake()->sentence(),
            'conversation_id' => $conversation->id,
        ]);

    Assistant::assertPrompted($message);
});

it('returns 403 when the conversation belongs to another user', function () {
    $conversation = AgentConversation::factory()->create(['user_id' => 999]);

    $this->actingAs($this->makeTestUser())
        ->postJson(route('chatbot.stream'), [
            'message' => fake()->sentence(),
            'conversation_id' => $conversation->id,
        ])->assertForbidden();
});

it('returns 403 when the conversation does not exist', function () {
    $this->actingAs($this->makeTestUser())
        ->postJson(route('chatbot.stream'), [
            'message' => fake()->sentence(),
            'conversation_id' => fake()->uuid(),
        ])->assertForbidden();
});

it('returns 422 when the message is missing', function () {
    $this->actingAs($this->makeTestUser())
        ->postJson(route('chatbot.stream'), [
            'conversation_id' => fake()->uuid(),
        ])->assertUnprocessable();
});

it('returns 422 when the conversation_id is missing', function () {
    $this->actingAs($this->makeTestUser())
        ->postJson(route('chatbot.stream'), [
            'message' => fake()->sentence(),
        ])->assertUnprocessable();
});