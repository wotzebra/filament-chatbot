<?php

namespace Wotz\FilamentChatbot\Tests\Streaming;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Wotz\FilamentChatbot\Agents\Assistant;
use Wotz\FilamentChatbot\Jobs\StreamAgentResponseJob;
use Wotz\FilamentChatbot\Models\AgentConversation;
use Wotz\FilamentChatbot\Streaming\TransportManager;
use Wotz\FilamentChatbot\Tests\TestCase;

class WebsocketStreamTransportTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('filament-chatbot.stream.transport', 'websocket');
        config()->set('filament-chatbot.stream.websocket.channel_prefix', 'chatbot.conversation');

        app()->forgetInstance(TransportManager::class);

        Assistant::fake(['Hello from the AI!']);

        $this->user = $this->makeTestUser();

        $this->bindFakeUserProvider();

        Context::forgetHidden('chatbot.context');
    }

    private function bindFakeUserProvider(): void
    {
        $testUser = $this->user;

        Auth::provider('fake-users', fn () => new class($testUser) implements UserProvider
        {
            public function __construct(private User $user) {}

            public function retrieveById($identifier): ?Authenticatable
            {
                return $identifier === $this->user->id ? $this->user : null;
            }

            public function retrieveByToken($identifier, $token): ?Authenticatable
            {
                return null;
            }

            public function updateRememberToken(Authenticatable $user, $token): void {}

            public function retrieveByCredentials(array $credentials): ?Authenticatable
            {
                return null;
            }

            public function validateCredentials(Authenticatable $user, array $credentials): bool
            {
                return false;
            }

            public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void {}
        });

        config()->set('auth.defaults.provider', 'users');
        config()->set('auth.providers.users', ['driver' => 'fake-users']);
    }

    #[Test]
    public function it_returns_a_202_json_acknowledgement_with_the_conversation_channel(): void
    {
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

        $this->assertSame(
            'chatbot.conversation.' . $conversation->id,
            $response->json('client.channel'),
        );
    }

    #[Test]
    public function it_dispatches_the_stream_agent_response_job_with_the_conversation_message_and_user(): void
    {
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
    }

    #[Test]
    public function it_does_not_dispatch_a_duplicate_job_when_the_same_stream_is_already_active_in_the_session(): void
    {
        Queue::fake();

        $conversation = AgentConversation::factory()->create(['user_id' => $this->user->id]);
        $message = fake()->sentence();

        session()->put($this->activeStreamsKey(), [
            $conversation->id => ['message' => $message],
        ]);

        $this->actingAs($this->user)
            ->postJson(route('chatbot.stream'), [
                'message' => $message,
                'conversation_id' => $conversation->id,
            ])->assertStatus(202);

        Queue::assertNotPushed(StreamAgentResponseJob::class);
    }

    #[Test]
    public function it_broadcasts_streamed_sdk_events_on_the_private_conversation_channel_when_the_job_runs(): void
    {
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
    }

    #[Test]
    public function it_broadcasts_a_friendly_error_event_when_the_agent_throws(): void
    {
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
    }
}
