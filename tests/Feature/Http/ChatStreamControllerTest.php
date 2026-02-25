<?php

use Wotz\FilamentChatbot\Agents\Assistant;
use Wotz\FilamentChatbot\Models\AgentConversation;

beforeEach(function () {
    Assistant::fake(['Hello from the AI!']);
});

it('returns 401 for unauthenticated requests', function () {
    $this->getJson(route('chatbot.stream', [
        'token' => fake()->uuid(),
        'message' => fake()->sentence(),
        'conversation_id' => fake()->uuid(),
    ]))->assertUnauthorized();
});

it('returns a streaming response for an authenticated request', function () {
    $conversation = AgentConversation::factory()->create();

    $this->actingAs($this->makeTestUser())
        ->get(route('chatbot.stream', [
            'token' => fake()->uuid(),
            'message' => fake()->sentence(),
            'conversation_id' => $conversation->id,
        ]))->assertOk();
});

it('records the prompt sent to the agent', function () {
    $conversation = AgentConversation::factory()->create();

    $this->actingAs($this->makeTestUser())
        ->get(route('chatbot.stream', [
            'token' => fake()->uuid(),
            'message' => $message = fake()->sentence(),
            'conversation_id' => $conversation->id,
        ]));

    Assistant::assertPrompted($message);
});

it('returns 422 when the message is missing', function () {
    $this->actingAs($this->makeTestUser())
        ->getJson(route('chatbot.stream', [
            'token' => fake()->uuid(),
            'conversation_id' => fake()->uuid(),
        ]))->assertUnprocessable();
});

it('returns 422 when the conversation_id is missing', function () {
    $this->actingAs($this->makeTestUser())
        ->getJson(route('chatbot.stream', [
            'token' => fake()->uuid(),
            'message' => fake()->sentence(),
        ]))->assertUnprocessable();
});
