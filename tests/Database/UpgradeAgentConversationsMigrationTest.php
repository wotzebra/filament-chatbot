<?php

namespace Wotz\FilamentChatbot\Tests\Database;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Ai\Enums\MessageStatus;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Wotz\FilamentChatbot\Models\AgentConversation;
use Wotz\FilamentChatbot\Models\AgentConversationMessage;
use Wotz\FilamentChatbot\Tests\TestCase;

class UpgradeAgentConversationsMigrationTest extends TestCase
{
    #[Test]
    public function it_moves_the_conversation_owner_to_the_participant_columns(): void
    {
        $this->createLegacyTables();
        config()->set('filament-chatbot.user_model', User::class);

        DB::table('agent_conversations')->insert([
            'id' => 'conversation-1',
            'user_id' => 7,
            'title' => 'Opening hours',
        ]);

        $this->upgradeMigration()->up();

        $dbConversation = AgentConversation::query()->find('conversation-1');

        $this->assertFalse(Schema::hasColumn('agent_conversations', 'user_id'));
        $this->assertSame(User::class, $dbConversation->participant_type);
        $this->assertSame(7, $dbConversation->participant_id);
    }

    #[Test]
    public function it_finishes_an_upgrade_that_stopped_halfway(): void
    {
        $this->createLegacyTables();
        config()->set('filament-chatbot.user_model', User::class);

        DB::table('agent_conversations')->insert(['id' => 'conversation-1', 'user_id' => 7, 'title' => 'Opening hours']);

        Schema::table('agent_conversations', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'updated_at']);
            $table->renameColumn('user_id', 'participant_id');
        });

        Schema::table('agent_conversations', function (Blueprint $table) {
            $table->string('participant_type')->nullable();
        });

        $this->upgradeMigration()->up();

        $this->assertSame(User::class, AgentConversation::query()->find('conversation-1')->participant_type);
        $this->assertTrue(Schema::hasIndex('agent_conversations', 'participant_updated_at_index'));
        $this->assertFalse(Schema::hasColumn('agent_conversation_messages', 'user_id'));
    }

    #[Test]
    public function it_stops_before_changing_the_tables_when_the_user_model_does_not_exist(): void
    {
        $this->createLegacyTables();
        config()->set('filament-chatbot.user_model', 'App\\Models\\Missing');

        try {
            $this->upgradeMigration()->up();

            $this->fail('The migration should not run without an existing user model.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('filament-chatbot.user_model', $exception->getMessage());
        }

        $this->assertTrue(Schema::hasColumn('agent_conversations', 'user_id'));
    }

    #[Test]
    public function it_rewrites_tool_calls_and_results_as_steps(): void
    {
        $this->createLegacyTables();

        $this->insertLegacyMessage('message-1', 'user', 'What is open today?');
        $this->insertLegacyMessage('message-2', 'assistant', 'The shop is open.', toolCalls: [
            ['id' => 'call_1', 'name' => 'OpeningHours', 'arguments' => []],
        ], toolResults: [
            ['id' => 'call_1', 'name' => 'OpeningHours', 'result' => '9:00 - 18:00'],
        ]);

        $this->upgradeMigration()->up();

        $dbUserMessage = AgentConversationMessage::query()->find('message-1');
        $dbAssistantMessage = AgentConversationMessage::query()->find('message-2');

        $this->assertFalse(Schema::hasColumn('agent_conversation_messages', 'tool_calls'));
        $this->assertFalse(Schema::hasColumn('agent_conversation_messages', 'tool_results'));
        $this->assertSame([], $dbUserMessage->steps);
        $this->assertSame(MessageStatus::Completed, $dbAssistantMessage->status);
        $this->assertSame('', $dbAssistantMessage->steps[0]['content']);
        $this->assertSame('9:00 - 18:00', $dbAssistantMessage->steps[0]['tool_calls'][0]['result']);
        $this->assertSame('The shop is open.', $dbAssistantMessage->steps[1]['content']);
    }

    #[Test]
    public function it_leaves_tables_that_already_have_the_new_schema_untouched(): void
    {
        $conversation = AgentConversation::factory()->ownedBy($this->makeTestUser())->create();
        $message = AgentConversationMessage::factory()->for($conversation, 'conversation')->create();

        $this->upgradeMigration()->up();

        $this->assertTrue(AgentConversationMessage::query()->whereKey($message->id)->exists());
        $this->assertSame(1, AgentConversation::query()->ownedBy($this->makeTestUser())->count());
    }

    protected function upgradeMigration(): Migration
    {
        return require __DIR__ . '/../../database/migrations/upgrade_agent_conversations_for_laravel_ai_v1.php';
    }

    protected function createLegacyTables(): void
    {
        Schema::drop('agent_conversation_messages');
        Schema::drop('agent_conversations');

        Schema::create('agent_conversations', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->foreignId('user_id')->nullable();
            $table->string('title');
            $table->timestamps();

            $table->index(['user_id', 'updated_at']);
        });

        Schema::create('agent_conversation_messages', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('conversation_id', 36)->index();
            $table->foreignId('user_id')->nullable();
            $table->string('agent');
            $table->string('role', 25);
            $table->longText('content');
            $table->longText('attachments');
            $table->longText('tool_calls');
            $table->longText('tool_results');
            $table->longText('usage');
            $table->longText('meta');
            $table->timestamps();

            $table->index(['conversation_id', 'user_id', 'updated_at'], 'conversation_index');
            $table->index(['user_id']);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $toolCalls
     * @param  list<array<string, mixed>>  $toolResults
     */
    protected function insertLegacyMessage(string $id, string $role, string $content, array $toolCalls = [], array $toolResults = []): void
    {
        DB::table('agent_conversation_messages')->insert([
            'id' => $id,
            'conversation_id' => 'conversation-1',
            'agent' => 'Assistant',
            'role' => $role,
            'content' => $content,
            'attachments' => '[]',
            'tool_calls' => json_encode($toolCalls),
            'tool_results' => json_encode($toolResults),
            'usage' => '[]',
            'meta' => '[]',
        ]);
    }
}
