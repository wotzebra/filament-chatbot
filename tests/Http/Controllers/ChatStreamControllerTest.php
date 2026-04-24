<?php

namespace Wotz\FilamentChatbot\Tests\Http\Controllers;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Wotz\FilamentChatbot\Agents\Assistant;
use Wotz\FilamentChatbot\Jobs\StreamAgentResponseJob;
use Wotz\FilamentChatbot\Models\AgentConversation;
use Wotz\FilamentChatbot\Tests\TestCase;

class ChatStreamControllerTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Assistant::fake(['Hello from the AI!']);

        $this->user = $this->makeTestUser();

        Context::forgetHidden('chatbot.context');
    }

    #[Test]
    public function it_returns_401_for_unauthenticated_requests(): void
    {
        $this->postJson(route('chatbot.stream'), [
            'message' => fake()->sentence(),
            'conversation_id' => fake()->uuid(),
        ])->assertUnauthorized();
    }

    #[Test]
    public function it_streams_a_response_for_an_authenticated_request(): void
    {
        $conversation = AgentConversation::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->post(route('chatbot.stream'), [
                'message' => fake()->sentence(),
                'conversation_id' => $conversation->id,
            ])->assertOk();
    }

    #[Test]
    public function it_forwards_the_user_message_to_the_agent(): void
    {
        $conversation = AgentConversation::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->post(route('chatbot.stream'), [
                'message' => $message = fake()->sentence(),
                'conversation_id' => $conversation->id,
            ])->streamedContent();

        Assistant::assertPrompted($message);
    }

    #[Test]
    public function it_trims_whitespace_from_the_user_message_before_forwarding_it_to_the_agent(): void
    {
        $conversation = AgentConversation::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->post(route('chatbot.stream'), [
                'message' => '  ' . ($message = fake()->sentence()) . '  ',
                'conversation_id' => $conversation->id,
            ])->streamedContent();

        Assistant::assertPrompted($message);
    }

    #[Test]
    public function it_returns_403_when_the_conversation_belongs_to_another_user(): void
    {
        $conversation = AgentConversation::factory()->create(['user_id' => 999]);

        $this->actingAs($this->user)
            ->postJson(route('chatbot.stream'), [
                'message' => fake()->sentence(),
                'conversation_id' => $conversation->id,
            ])->assertForbidden();
    }

    #[Test]
    public function it_returns_403_when_the_conversation_does_not_exist(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('chatbot.stream'), [
                'message' => fake()->sentence(),
                'conversation_id' => fake()->uuid(),
            ])->assertForbidden();
    }

    #[Test]
    #[DataProvider('missingFieldProvider')]
    public function it_returns_422_when_a_required_field_is_missing(array $payload): void
    {
        $this->actingAs($this->user)
            ->postJson(route('chatbot.stream'), $payload)
            ->assertUnprocessable();
    }

    public static function missingFieldProvider(): array
    {
        return [
            'missing message' => [['conversation_id' => '8e1a1f8d-1a2b-4c3d-9e4f-5a6b7c8d9e0f']],
            'missing conversation_id' => [['message' => 'hello']],
        ];
    }

    #[Test]
    public function it_returns_422_when_the_context_is_not_an_array(): void
    {
        $conversation = AgentConversation::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->postJson(route('chatbot.stream'), [
                'message' => fake()->sentence(),
                'conversation_id' => $conversation->id,
                'context' => 'not-an-array',
            ])->assertUnprocessable();
    }

    #[Test]
    public function it_resolves_context_via_the_plugin_resolver_and_stores_it_in_laravel_context(): void
    {
        $conversation = AgentConversation::factory()->create(['user_id' => $this->user->id]);

        $this->chatbotPlugin->contextResolver(
            fn (array $context) => $context === [] ? null : 'resolved: ' . json_encode($context),
        );

        $this->actingAs($this->user)
            ->post(route('chatbot.stream'), [
                'message' => fake()->sentence(),
                'conversation_id' => $conversation->id,
                'context' => ['type' => 'order', 'id' => 42],
            ])->streamedContent();

        $this->assertSame(
            'resolved: {"type":"order","id":42}',
            Context::getHidden('chatbot.context'),
        );
    }

    #[Test]
    public function it_stores_null_in_laravel_context_when_no_context_is_sent(): void
    {
        $conversation = AgentConversation::factory()->create(['user_id' => $this->user->id]);

        Context::addHidden('chatbot.context', 'stale');

        $this->actingAs($this->user)
            ->post(route('chatbot.stream'), [
                'message' => fake()->sentence(),
                'conversation_id' => $conversation->id,
            ])->streamedContent();

        $this->assertNull(Context::getHidden('chatbot.context'));
    }

    #[Test]
    public function it_can_force_the_http_transport_when_websocket_is_configured(): void
    {
        config()->set('filament-chatbot.stream.transport', 'websocket');

        $conversation = AgentConversation::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->post(route('chatbot.stream'), [
                'message' => fake()->sentence(),
                'conversation_id' => $conversation->id,
                'transport' => 'http',
            ]);

        $response->assertOk();

        $this->assertInstanceOf(StreamedResponse::class, $response->baseResponse);
        $this->assertStringContainsString('text/event-stream', $response->headers->get('Content-Type'));
    }

    #[Test]
    public function it_does_not_queue_a_duplicate_websocket_stream_job_when_the_same_request_is_already_active(): void
    {
        config()->set('filament-chatbot.stream.transport', 'websocket');
        Queue::fake();

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
    }
}
