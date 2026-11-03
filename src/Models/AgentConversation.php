<?php

namespace Wotz\FilamentChatbot\Models;

use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Wotz\FilamentChatbot\Database\Factories\AgentConversationFactory;
use Wotz\FilamentChatbot\Models\Builders\AgentConversationBuilder;

/**
 * @property string $id
 * @property string|null $participant_type
 * @property int|null $participant_id
 * @property string $title
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseEloquentBuilder(AgentConversationBuilder::class)]
class AgentConversation extends Model
{
    /** @use HasFactory<AgentConversationFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function participant(): MorphTo
    {
        return $this->morphTo();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AgentConversationMessage::class, 'conversation_id');
    }
}
