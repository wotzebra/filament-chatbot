<?php

namespace Wotz\FilamentChatbot\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;
use Wotz\FilamentChatbot\Http\Requests\ChatStreamRequest;
use Wotz\FilamentChatbot\Support\Chatbot\ToolRegistry;

class ChatStreamController
{
    public function __invoke(ChatStreamRequest $request): mixed
    {
        set_time_limit(300);

        $conversationId = $request->conversationId();

        $exists = DB::table('agent_conversations')
            ->where('id', $conversationId)
            ->where('user_id', auth()->id())
            ->exists();

        abort_unless($exists, 403);

        /** @var ChatbotPlugin $chatbot */
        $chatbot = filament('chatbot');

        app(ToolRegistry::class)->withTools($chatbot->getTools());

        $agentClass = $chatbot->getAgentClass();

        /** @var Agent $agent */
        $agent = new $agentClass;

        if ($agent instanceof Conversational && method_exists($agent, 'continue')) {
            $agent->continue($conversationId, as: auth()->user());
        }

        return $agent->stream(
            $request->message(),
            provider: $chatbot->getProvider(),
            model: $chatbot->getModel(),
        );
    }
}
