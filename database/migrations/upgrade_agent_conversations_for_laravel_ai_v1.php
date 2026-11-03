<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Ai\Enums\MessageStatus;

/**
 * Moves conversation tables created for laravel/ai 0.x to the laravel/ai 1.x schema.
 *
 * Every step checks the current columns and indexes, so the migration does nothing on
 * tables created with the 1.x schema, and can be run again after it failed halfway.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->replaceUserIdWithParticipant();
        $this->replaceToolColumnsWithSteps();
    }

    protected function replaceUserIdWithParticipant(): void
    {
        $participantType = $this->participantType();

        if (Schema::hasColumn('agent_conversations', 'user_id')) {
            Schema::table('agent_conversations', function (Blueprint $table) {
                $table->dropIndex(['user_id', 'updated_at']);
                $table->renameColumn('user_id', 'participant_id');
            });
        }

        if (! Schema::hasColumn('agent_conversations', 'participant_type')) {
            Schema::table('agent_conversations', function (Blueprint $table) {
                $table->string('participant_type')->nullable()->after('id');
            });
        }

        if (! Schema::hasIndex('agent_conversations', 'participant_updated_at_index')) {
            Schema::table('agent_conversations', function (Blueprint $table) {
                $table->index(['participant_type', 'participant_id', 'updated_at'], 'participant_updated_at_index');
            });
        }

        if (Schema::hasColumn('agent_conversation_messages', 'user_id')) {
            Schema::table('agent_conversation_messages', function (Blueprint $table) {
                $table->dropIndex('conversation_index');
                $table->dropIndex(['user_id']);
                $table->renameColumn('user_id', 'participant_id');
            });
        }

        if (! Schema::hasColumn('agent_conversation_messages', 'participant_type')) {
            Schema::table('agent_conversation_messages', function (Blueprint $table) {
                $table->string('participant_type')->nullable()->after('conversation_id');
            });
        }

        if (! Schema::hasIndex('agent_conversation_messages', 'conversation_index')) {
            Schema::table('agent_conversation_messages', function (Blueprint $table) {
                $table->index(['conversation_id', 'participant_type', 'participant_id', 'updated_at'], 'conversation_index');
            });
        }

        foreach (['agent_conversations', 'agent_conversation_messages'] as $table) {
            DB::table($table)
                ->whereNull('participant_type')
                ->whereNotNull('participant_id')
                ->update(['participant_type' => $participantType]);
        }
    }

    protected function replaceToolColumnsWithSteps(): void
    {
        if (! Schema::hasColumn('agent_conversation_messages', 'tool_calls')) {
            return;
        }

        if (! Schema::hasColumn('agent_conversation_messages', 'steps')) {
            Schema::table('agent_conversation_messages', function (Blueprint $table) {
                $table->longText('steps')->nullable();
                $table->string('status', 25)->default(MessageStatus::Completed->value);
            });
        }

        DB::table('agent_conversation_messages')->where('role', 'user')->update(['steps' => '[]']);

        DB::table('agent_conversation_messages')
            ->select('conversation_id')
            ->distinct()
            ->orderBy('conversation_id')
            ->chunk(100, function (Collection $conversations) {
                foreach ($conversations as $conversation) {
                    $this->backfillSteps($conversation->conversation_id);
                }
            });

        $obsoleteColumns = array_values(array_filter(
            ['tool_calls', 'tool_results', 'approval_state'],
            fn (string $column) => Schema::hasColumn('agent_conversation_messages', $column),
        ));

        $hasParticipantIndex = Schema::hasIndex('agent_conversation_messages', 'participant_index');

        Schema::table('agent_conversation_messages', function (Blueprint $table) use ($obsoleteColumns, $hasParticipantIndex) {
            $table->longText('steps')->nullable(false)->change();
            $table->dropColumn($obsoleteColumns);

            if ($hasParticipantIndex) {
                $table->dropIndex('participant_index');
            }

            $table->index(['participant_type', 'participant_id', 'agent'], 'participant_index');
        });
    }

    /**
     * Rewrites the assistant messages of one conversation as steps, attaching each tool result to the call that produced it.
     */
    protected function backfillSteps(string $conversationId): void
    {
        $messages = DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->where('role', 'assistant')
            ->orderBy('id')
            ->get();

        $results = $messages->flatMap(fn (object $message) => $this->decode($message->tool_results))->keyBy('id');

        foreach ($messages as $message) {
            $meta = $this->decode($message->meta);

            $calls = collect($this->decode($message->tool_calls))
                ->filter(fn (array $call) => $results->has($call['id'] ?? ''))
                ->map(fn (array $call) => [
                    ...$call,
                    'result' => $results[$call['id']]['result'] ?? null,
                    ...array_filter([
                        'denied' => $results[$call['id']]['denied'] ?? false,
                        'failed' => $results[$call['id']]['failed'] ?? false,
                    ]),
                ])
                ->values()
                ->all();

            $content = (string) $message->content;

            $steps = $calls !== [] && $content !== ''
                ? [$this->step('', $calls), $this->step($content, [], $meta['reasoning'] ?? '')]
                : [$this->step($content, $calls, $meta['reasoning'] ?? '')];

            unset($meta['provider_steps'], $meta['provider_content_blocks'], $meta['reasoning']);

            DB::table('agent_conversation_messages')->where('id', $message->id)->update([
                'steps' => json_encode($steps),
                'meta' => json_encode($meta),
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $calls
     * @return array{content: string, tool_calls: list<array<string, mixed>>, reasoning: string, replay_blocks: array{}, provider_tool_calls: array{}}
     */
    protected function step(string $content, array $calls = [], string $reasoning = ''): array
    {
        return [
            'content' => $content,
            'tool_calls' => $calls,
            'reasoning' => $reasoning,
            'replay_blocks' => [],
            'provider_tool_calls' => [],
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function decode(?string $json): array
    {
        $decoded = json_decode($json ?? '', true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * The morph class of the model that owns the existing conversations, resolved before any column changes.
     *
     * @throws RuntimeException
     */
    protected function participantType(): ?string
    {
        $needsParticipantType = Schema::hasColumn('agent_conversations', 'user_id') ||
            Schema::hasColumn('agent_conversation_messages', 'user_id') ||
            DB::table('agent_conversations')->whereNull('participant_type')->whereNotNull('participant_id')->exists();

        if (! $needsParticipantType) {
            return null;
        }

        $userModel = config('filament-chatbot.user_model') ?: config('auth.providers.users.model');

        if (! is_string($userModel) || ! is_subclass_of($userModel, Model::class)) {
            throw new RuntimeException('Set filament-chatbot.user_model to the model that owns the conversations before running this migration.');
        }

        return (new $userModel)->getMorphClass();
    }
};
