<?php

namespace Wotz\FilamentChatbot\Models;

use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Ai\Enums\MessageStatus;
use Wotz\FilamentChatbot\Database\Factories\AgentConversationMessageFactory;
use Wotz\FilamentChatbot\Models\Builders\AgentConversationMessageBuilder;

/**
 * @property string $id
 * @property string $conversation_id
 * @property string|null $participant_type
 * @property int|null $participant_id
 * @property string $agent
 * @property string $role
 * @property string $content
 * @property array<int, array<string, mixed>> $steps
 * @property MessageStatus $status
 */
#[UseEloquentBuilder(AgentConversationMessageBuilder::class)]
class AgentConversationMessage extends Model
{
    /** @use HasFactory<AgentConversationMessageFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'steps' => 'array',
            'usage' => 'array',
            'meta' => 'array',
            'status' => MessageStatus::class,
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AgentConversation::class, 'conversation_id');
    }
}
