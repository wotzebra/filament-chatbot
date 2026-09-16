<?php

namespace Wotz\FilamentChatbot\Tests\Filament\Resources;

use Illuminate\Foundation\Auth\User;
use Livewire\Livewire;
use Orchestra\Testbench\Attributes\WithMigration;
use PHPUnit\Framework\Attributes\Test;
use Wotz\FilamentChatbot\Filament\Resources\ConversationResource\Pages\ListConversations;
use Wotz\FilamentChatbot\Filament\Resources\ConversationResource\Pages\ViewConversation;
use Wotz\FilamentChatbot\Models\AgentConversation;
use Wotz\FilamentChatbot\Models\AgentConversationMessage;
use Wotz\FilamentChatbot\Tests\TestCase;

#[WithMigration]
class ConversationResourceTest extends TestCase
{
    public function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        config()->set('auth.providers.users.model', User::class);
    }

    protected function makeTestUser(): User
    {
        return User::query()->forceCreate([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret',
        ]);
    }

    #[Test]
    public function it_lists_only_the_conversations_of_the_authenticated_user(): void
    {
        $user = $this->makeTestUser();

        $ownConversation = AgentConversation::factory()->create(['user_id' => $user->id]);
        $otherConversation = AgentConversation::factory()->create(['user_id' => 999]);

        $this->actingAs($user);

        Livewire::test(ListConversations::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$ownConversation])
            ->assertCanNotSeeTableRecords([$otherConversation]);
    }

    #[Test]
    public function it_renders_the_conversation_view_page_with_its_messages(): void
    {
        $user = $this->makeTestUser();

        $conversation = AgentConversation::factory()->create(['user_id' => $user->id]);

        AgentConversationMessage::factory()->user()->for($conversation, 'conversation')->create(['content' => 'Hello from the user']);
        AgentConversationMessage::factory()->assistant()->for($conversation, 'conversation')->create(['content' => 'Hello from the assistant']);

        $this->actingAs($user);

        Livewire::test(ViewConversation::class, ['record' => $conversation->getKey()])
            ->assertOk()
            ->assertSee('Hello from the user')
            ->assertSee('Hello from the assistant');
    }
}
