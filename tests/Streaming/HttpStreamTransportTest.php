<?php

use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Wotz\FilamentChatbot\Agents\Assistant;
use Wotz\FilamentChatbot\Models\AgentConversation;

beforeEach(function () {
    config()->set('filament-chatbot.stream.transport', 'http');

    Assistant::fake(['Hello from the AI!']);

    $this->user = $this->makeTestUser();

    Context::forgetHidden('chatbot.context');
});

it('returns a StreamedResponse with SSE headers', function () {
    $conversation = AgentConversation::factory()->create(['user_id' => $this->user->id]);

    $response = $this->actingAs($this->user)
        ->post(route('chatbot.stream'), [
            'message' => fake()->sentence(),
            'conversation_id' => $conversation->id,
        ]);

    $response->assertOk();

    expect($response->baseResponse)->toBeInstanceOf(StreamedResponse::class)
        ->and($response->headers->get('Content-Type'))->toContain('text/event-stream')
        ->and($response->headers->get('Cache-Control'))->toContain('no-cache')
        ->and($response->headers->get('X-Accel-Buffering'))->toBe('no');
});

it('emits SSE data lines and terminates with the [DONE] marker', function () {
    $conversation = AgentConversation::factory()->create(['user_id' => $this->user->id]);

    $response = $this->actingAs($this->user)
        ->post(route('chatbot.stream'), [
            'message' => fake()->sentence(),
            'conversation_id' => $conversation->id,
        ]);

    $body = $response->streamedContent();

    expect($body)->toContain('data: ')
        ->and($body)->toContain('[DONE]');
});

it('emits a friendly text_delta and [DONE] when the agent throws', function () {
    Assistant::fake(fn () => throw new RuntimeException('upstream blew up'));

    $conversation = AgentConversation::factory()->create(['user_id' => $this->user->id]);

    $response = $this->actingAs($this->user)
        ->post(route('chatbot.stream'), [
            'message' => fake()->sentence(),
            'conversation_id' => $conversation->id,
        ]);

    $body = $response->streamedContent();

    expect($body)->toContain('text_delta')
        ->and($body)->toContain('[DONE]');
});
