<?php

namespace Wotz\FilamentChatbot\Livewire;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Ai\Messages\MessageRole;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;

class ChatbotWidget extends Component
{
    public Collection $messages;

    public string $question = '';

    #[Locked]
    public ?string $conversationId = null;

    public bool $isStreaming = false;

    public string $streamMessage = '';

    /** @var array<string, mixed> */
    public array $pageContext = [];

    #[Locked]
    public string $name;

    #[Locked]
    public string $buttonText;

    #[Locked]
    public string $buttonIcon;

    #[Locked]
    public string $welcomeMessage;

    #[Locked]
    public string $winWidth;

    #[Locked]
    public string $winHeight;

    public string $winPosition;

    #[Locked]
    public bool $showPositionBtn;

    public bool $panelHidden;

    #[Locked]
    public string|false $logoUrl;

    #[Locked]
    public string $streamRouteBase;

    public function mount(): void
    {
        $this->conversationId = session()->get($this->conversationSessionKey());

        if (! $this->conversationId) {
            $this->messages = collect();
        } else {
            $this->messages = collect(
                DB::table('agent_conversation_messages')
                    ->where('conversation_id', $this->conversationId)
                    ->whereIn('role', [MessageRole::User->value, MessageRole::Assistant->value])
                    ->orderBy('created_at')
                    ->get(['role', 'content']),
            )->map(fn ($row) => (object) ['role' => $row->role, 'content' => $row->content]);
        }

        /** @var ChatbotPlugin $chatbot */
        $chatbot = filament('chatbot');

        $this->panelHidden = session()->get('chatbot-panel-hidden', true);
        $this->winWidth = 'width:' . $chatbot->getChatWidth() . ';';
        $this->winHeight = 'height:' . $chatbot->getChatHeight() . ';';
        $this->winPosition = session()->get('chatbot-win-position', '');
        $this->showPositionBtn = true;
        $this->name = $chatbot->getBotName();
        $this->welcomeMessage = $chatbot->getWelcomeMessage();
        $this->buttonText = $chatbot->getButtonText();
        $this->buttonIcon = $chatbot->getButtonIcon();
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

        if (! $this->conversationId) {
            $this->conversationId = DB::transaction(function () use ($message): string {
                $conversationId = (string) Str::uuid();

                DB::table('agent_conversations')->insert([
                    'id' => $conversationId,
                    'user_id' => auth()->id(),
                    'title' => Str::limit($message, 80),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return $conversationId;
            });

            session()->put($this->conversationSessionKey(), $this->conversationId);
        }

        $this->streamMessage = $message;
        $this->isStreaming = true;
    }

    public function onStreamComplete(string $assistantMessage = ''): void
    {
        if ($assistantMessage !== '' && $this->conversationId) {
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

        if ($assistantMessage === '' && $this->conversationId) {
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

    public function changeWinPosition(): void
    {
        if ($this->winPosition !== 'left') {
            $this->winPosition = 'left';
        } else {
            $this->winPosition = '';
        }

        session()->put('chatbot-win-position', $this->winPosition);
    }

    #[On('chatbot:context-updated')]
    public function setPageContext(array $context = []): void
    {
        $this->pageContext = $context;
    }

    public function clearChat(): void
    {
        session()->forget($this->conversationSessionKey());
        $this->conversationId = null;
        $this->messages = collect();
        $this->isStreaming = false;
        $this->streamMessage = '';
    }

    public function togglePanel(): void
    {
        $this->panelHidden = ! $this->panelHidden;

        session()->put('chatbot-panel-hidden', $this->panelHidden);
    }

    public function render(): View
    {
        return view('filament-chatbot::chatbot-widget');
    }

    protected function conversationSessionKey(): string
    {
        /** @var ChatbotPlugin $chatbot */
        $chatbot = filament('chatbot');

        return $chatbot->getConversationKey();
    }
}
