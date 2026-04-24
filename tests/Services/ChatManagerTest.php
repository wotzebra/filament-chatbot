<?php

namespace Wotz\FilamentChatbot\Tests\Services;

use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Wotz\FilamentChatbot\Services\ChatManager;
use Wotz\FilamentChatbot\Tests\TestCase;

class ChatManagerTest extends TestCase
{
    #[Test]
    public function it_starts_a_conversation_for_the_given_user(): void
    {
        $user = $this->makeTestUser();

        $conversation = app(ChatManager::class)->start(user: $user);

        $this->assertSame($user->id, $conversation->user_id);
    }

    #[Test]
    public function it_truncates_a_long_title_to_80_characters(): void
    {
        $conversation = app(ChatManager::class)->start(
            user: $this->makeTestUser(),
            title: str_repeat('A', 120),
        );

        $this->assertSame(Str::limit(str_repeat('A', 120), 80), $conversation->title);
    }

    #[Test]
    public function it_defaults_the_title_to_an_empty_string_when_omitted(): void
    {
        $conversation = app(ChatManager::class)->start(user: $this->makeTestUser());

        $this->assertSame('', $conversation->title);
    }
}
