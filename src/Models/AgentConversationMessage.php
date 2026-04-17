<?php

namespace Wotz\FilamentChatbot\Models;

use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Wotz\FilamentChatbot\Database\Factories\AgentConversationMessageFactory;
use Wotz\FilamentChatbot\Models\Builders\AgentConversationMessageBuilder;

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
            'tool_calls' => 'array',
            'tool_results' => 'array',
            'usage' => 'array',
            'meta' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AgentConversation::class, 'conversation_id');
    }
}
