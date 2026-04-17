<?php

namespace Wotz\FilamentChatbot\Livewire;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Laravel\Ai\Messages\MessageRole;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;

class ChatbotConversation extends Component
{
    public Collection $messages;

    public string $question = '';

    #[Locked]
    public string $conversationId;

    public bool $isStreaming = false;

    public string $streamMessage = '';

    #[Locked]
    public string $streamRouteBase;

    #[Locked]
    public string|false $logoUrl;

    public function mount(string $conversationId): void
    {
        abort_unless(
            DB::table('agent_conversations')
                ->where('id', $conversationId)
                ->where('user_id', auth()->id())
                ->exists(),
            403,
        );

        $this->conversationId = $conversationId;

        $this->messages = collect(
            DB::table('agent_conversation_messages')
                ->where('conversation_id', $this->conversationId)
                ->whereIn('role', [MessageRole::User->value, MessageRole::Assistant->value])
                ->orderBy('created_at')
                ->get(['role', 'content']),
        )->map(fn ($row) => (object) ['role' => $row->role, 'content' => $row->content]);

        /** @var ChatbotPlugin $chatbot */
        $chatbot = filament('chatbot');

        $this->logoUrl = $chatbot->getLogoUrl() ?? false;
        $this->streamRouteBase = route('chatbot.stream');
    }

    public function askQuestion(): void
    {
        if ($this->isStreaming) {
            return;
        }

        if (empty(trim($this->question))) {
            $this->question = '';

            return;
        }

        $message = $this->question;
        $this->question = '';

        $this->messages->push((object) ['role' => MessageRole::User->value, 'content' => $message]);

        $this->streamMessage = $message;
        $this->isStreaming = true;
    }

    public function onStreamComplete(string $assistantMessage = ''): void
    {
        if ($assistantMessage !== '') {
            $latestAssistantMessage = DB::table('agent_conversation_messages')
                ->where('conversation_id', $this->conversationId)
                ->where('role', MessageRole::Assistant->value)
                ->latest('created_at')
                ->first(['id', 'content']);

            if ($latestAssistantMessage && $latestAssistantMessage->content === '') {
                DB::table('agent_conversation_messages')
                    ->where('id', $latestAssistantMessage->id)
                    ->update([
                        'content' => $assistantMessage,
                        'updated_at' => now(),
                    ]);
            }
        }

        if ($assistantMessage === '') {
            $assistantMessage = (string) DB::table('agent_conversation_messages')
                ->where('conversation_id', $this->conversationId)
                ->where('role', MessageRole::Assistant->value)
                ->latest('created_at')
                ->value('content');
        }

        $lastMessage = $this->messages->last();

        if ($assistantMessage !== '' && (! $lastMessage || $lastMessage->role !== MessageRole::Assistant->value || $lastMessage->content !== $assistantMessage)) {
            $this->messages->push((object) ['role' => MessageRole::Assistant->value, 'content' => $assistantMessage]);
        }

        $this->isStreaming = false;
        $this->streamMessage = '';
    }

    public function render(): View
    {
        return view('filament-chatbot::chatbot-conversation');
    }
}
