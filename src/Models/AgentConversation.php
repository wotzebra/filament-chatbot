<?php

namespace Wotz\FilamentChatbot\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Wotz\FilamentChatbot\Database\Factories\AgentConversationFactory;

class AgentConversation extends Model
{
    /** @use HasFactory<AgentConversationFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(filament('chatbot')->getUserModel(), 'user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AgentConversationMessage::class, 'conversation_id');
    }
}
