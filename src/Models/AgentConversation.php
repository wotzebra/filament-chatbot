<?php

namespace Wotz\FilamentChatbot\Models;

use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Wotz\FilamentChatbot\Database\Factories\AgentConversationFactory;
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;
use Wotz\FilamentChatbot\Models\Builders\AgentConversationBuilder;

/**
 * @property string $id
 * @property int|null $user_id
 * @property string $title
 */
#[UseEloquentBuilder(AgentConversationBuilder::class)]
class AgentConversation extends Model
{
    /** @use HasFactory<AgentConversationFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        /** @var ChatbotPlugin $chatbot */
        $chatbot = filament('chatbot');

        return $this->belongsTo($chatbot->getUserModel(), 'user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AgentConversationMessage::class, 'conversation_id');
    }
}
