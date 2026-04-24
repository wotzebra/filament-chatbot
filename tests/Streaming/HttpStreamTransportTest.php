<?php

namespace Wotz\FilamentChatbot\Tests\Streaming;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Context;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Wotz\FilamentChatbot\Agents\Assistant;
use Wotz\FilamentChatbot\Models\AgentConversation;
use Wotz\FilamentChatbot\Tests\TestCase;

class HttpStreamTransportTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('filament-chatbot.stream.transport', 'http');

        Assistant::fake(['Hello from the AI!']);

        $this->user = $this->makeTestUser();

        Context::forgetHidden('chatbot.context');
    }

    #[Test]
    public function it_returns_a_streamed_response_with_sse_headers(): void
    {
        $conversation = AgentConversation::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->post(route('chatbot.stream'), [
                'message' => fake()->sentence(),
                'conversation_id' => $conversation->id,
            ]);

        $response->assertOk();

        $this->assertInstanceOf(StreamedResponse::class, $response->baseResponse);
        $this->assertStringContainsString('text/event-stream', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
        $this->assertSame('no', $response->headers->get('X-Accel-Buffering'));
    }

    #[Test]
    public function it_emits_sse_data_lines_and_terminates_with_the_done_marker(): void
    {
        $conversation = AgentConversation::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->post(route('chatbot.stream'), [
                'message' => fake()->sentence(),
                'conversation_id' => $conversation->id,
            ]);

        $body = $response->streamedContent();

        $this->assertStringContainsString('data: ', $body);
        $this->assertStringContainsString('[DONE]', $body);
    }

    #[Test]
    public function it_emits_a_friendly_text_delta_and_done_when_the_agent_throws(): void
    {
        Assistant::fake(fn () => throw new RuntimeException('upstream blew up'));

        $conversation = AgentConversation::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->post(route('chatbot.stream'), [
                'message' => fake()->sentence(),
                'conversation_id' => $conversation->id,
            ]);

        $body = $response->streamedContent();

        $this->assertStringContainsString('text_delta', $body);
        $this->assertStringContainsString('[DONE]', $body);
    }
}
