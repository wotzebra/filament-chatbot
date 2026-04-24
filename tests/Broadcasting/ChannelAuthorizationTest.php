<?php

namespace Wotz\FilamentChatbot\Tests\Broadcasting;

use Closure;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Broadcast;
use PHPUnit\Framework\Attributes\Test;
use Wotz\FilamentChatbot\Models\AgentConversation;
use Wotz\FilamentChatbot\Tests\TestCase;

class ChannelAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('filament-chatbot.stream.websocket.channel_prefix', 'chatbot.conversation');
    }

    private function chatbotChannelCallback(): ?Closure
    {
        $pattern = 'chatbot.conversation.{conversationId}';

        return Broadcast::driver()->getChannels()->get($pattern);
    }

    #[Test]
    public function it_authorizes_the_conversation_owner_on_the_private_channel(): void
    {
        $owner = $this->makeTestUser();
        $conversation = AgentConversation::factory()->create(['user_id' => $owner->id]);

        $callback = $this->chatbotChannelCallback();

        $this->assertNotNull($callback);
        $this->assertTrue($callback($owner, $conversation->id));
    }

    #[Test]
    public function it_rejects_a_user_that_does_not_own_the_conversation(): void
    {
        $owner = $this->makeTestUser();
        $conversation = AgentConversation::factory()->create(['user_id' => $owner->id]);

        $stranger = new User;
        $stranger->id = 999;
        $stranger->name = 'Other User';

        $callback = $this->chatbotChannelCallback();

        $this->assertNotNull($callback);
        $this->assertFalse($callback($stranger, $conversation->id));
    }

    #[Test]
    public function it_rejects_access_to_a_non_existent_conversation(): void
    {
        $owner = $this->makeTestUser();

        $callback = $this->chatbotChannelCallback();

        $this->assertNotNull($callback);
        $this->assertFalse($callback($owner, fake()->uuid()));
    }
}
