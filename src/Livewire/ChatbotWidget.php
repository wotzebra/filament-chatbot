<?php

namespace Wotz\FilamentChatbot\Livewire;

use Illuminate\Support\Collection;
use Illuminate\View\View;
use Laravel\Ai\Messages\MessageRole;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Wotz\FilamentChatbot\Contracts\StreamTransport;
use Wotz\FilamentChatbot\Facades\Chat;
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;

class ChatbotWidget extends Component
{
    #[Locked]
    public bool $standalone = false;

    #[Locked]
    public ?string $conversationId = null;

    public Collection $messages;

    public string $question = '';

    public bool $isStreaming = false;

    public string $streamMessage = '';

    /** @var array<string, mixed> */
    public array $pageContext = [];

    #[Locked]
    public string $streamRouteBase;

    #[Locked]
    public string|false $logoUrl;

    #[Locked]
    public string $name = '';

    #[Locked]
    public string $buttonText = '';

    #[Locked]
    public string $buttonIcon = '';

    #[Locked]
    public string $welcomeMessage = '';

    #[Locked]
    public string $winWidth = '';

    #[Locked]
    public string $winHeight = '';

    public string $winPosition = '';

    #[Locked]
    public bool $showPositionBtn = true;

    public bool $panelHidden = true;

    public function mount(?string $conversationId = null): void
    {
        $chatbot = $this->chatbot();
        $this->hydrateWidgetChrome($chatbot);

        if ($conversationId !== null) {
            abort_unless(Chat::ownedBy($conversationId, auth()->user()), 403);

            $this->standalone = true;
            $this->conversationId = $conversationId;
        } else {
            $this->conversationId = $this->resolveActiveConversationId(
                session()->get($this->conversationSessionKey()),
            );
        }

        $this->messages = $this->conversationId
            ? Chat::history($this->conversationId)
            : collect();

        $this->logoUrl = $chatbot->getLogoUrl() ?? false;
        $this->streamRouteBase = route('chatbot.stream');
    }

    public function askQuestion(): void
    {
        if ($this->isStreaming) {
            return;
        }

        $message = trim($this->question);
        $this->question = '';

        if ($message === '') {
            return;
        }

        $this->messages->push([
            'role' => MessageRole::User->value,
            'content' => $message,
        ]);

        $this->streamMessage = $message;
        $this->isStreaming = true;

        if (! $this->conversationId) {
            $this->conversationId = Chat::start(auth()->user(), $message)->id;

            session()->put($this->conversationSessionKey(), $this->conversationId);
        }
    }

    public function onStreamComplete(string $assistantMessage = ''): void
    {
        $resolved = $this->conversationId
            ? Chat::for($this->conversationId)->finalize($assistantMessage)
            : $assistantMessage;

        $last = $this->messages->last();
        $alreadyShown = $last
            && ($last['role'] ?? null) === MessageRole::Assistant->value
            && ($last['content'] ?? null) === $resolved;

        if ($resolved !== '' && ! $alreadyShown) {
            $this->messages->push([
                'role' => MessageRole::Assistant->value,
                'content' => $resolved,
            ]);
        }

        $this->isStreaming = false;
        $this->streamMessage = '';
    }

    /**
     * @return array{name: string, config: array<string, mixed>}
     */
    public function streamTransport(): array
    {
        $transport = app(StreamTransport::class);

        return [
            'name' => $transport->name(),
            'config' => $transport->clientConfig($this->conversationId ?? ''),
        ];
    }

    public function changeWinPosition(): void
    {
        $this->winPosition = $this->winPosition === 'left' ? '' : 'left';

        session()->put('chatbot-win-position', $this->winPosition);
    }

    public function togglePanel(): void
    {
        $this->panelHidden = ! $this->panelHidden;

        session()->put('chatbot-panel-hidden', $this->panelHidden);
    }

    public function clearChat(): void
    {
        if ($this->standalone) {
            return;
        }

        session()->forget($this->conversationSessionKey());

        $this->conversationId = null;
        $this->messages = collect();
        $this->isStreaming = false;
        $this->streamMessage = '';
    }

    #[On('chatbot:context-updated')]
    public function setPageContext(array $context = []): void
    {
        $this->pageContext = $context;
    }

    public function render(): View
    {
        return view('filament-chatbot::chatbot-widget');
    }

    protected function hydrateWidgetChrome(ChatbotPlugin $chatbot): void
    {
        $this->panelHidden = session()->get('chatbot-panel-hidden', true);
        $this->winPosition = session()->get('chatbot-win-position', '');
        $this->winWidth = 'width:' . $chatbot->getChatWidth() . ';';
        $this->winHeight = 'height:' . $chatbot->getChatHeight() . ';';
        $this->name = $chatbot->getBotName();
        $this->welcomeMessage = $chatbot->getWelcomeMessage();
        $this->buttonText = $chatbot->getButtonText();
        $this->buttonIcon = $chatbot->getButtonIcon();
    }

    protected function resolveActiveConversationId(?string $conversationId): ?string
    {
        if ($conversationId === null || $conversationId === '') {
            return null;
        }

        if (Chat::ownedBy($conversationId, auth()->user())) {
            return $conversationId;
        }

        session()->forget($this->conversationSessionKey());

        return null;
    }

    protected function conversationSessionKey(): string
    {
        return $this->chatbot()->getConversationKey();
    }

    protected function chatbot(): ChatbotPlugin
    {
        /** @var ChatbotPlugin $plugin */
        $plugin = filament('chatbot');

        return $plugin;
    }
}
