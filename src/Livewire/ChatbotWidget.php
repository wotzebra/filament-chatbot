<?php

namespace Wotz\FilamentChatbot\Livewire;

use Illuminate\Support\Collection;
use Illuminate\View\View;
use Laravel\Ai\Messages\MessageRole;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Wotz\FilamentChatbot\Contracts\StreamTransport;
use Wotz\FilamentChatbot\Facades\Chat;
use Wotz\FilamentChatbot\Filament\Plugins\ChatbotPlugin;
use Wotz\FilamentChatbot\Livewire\Concerns\HasAppearance;
use Wotz\FilamentChatbot\Livewire\Concerns\HasPageContext;
use Wotz\FilamentChatbot\Livewire\Concerns\HasStreamingState;
use Wotz\FilamentChatbot\Models\AgentConversation;

class ChatbotWidget extends Component
{
    use HasAppearance;
    use HasPageContext;
    use HasStreamingState;

    #[Locked]
    public bool $standalone = false;

    #[Locked]
    public ?string $conversationId = null;

    /** @var array<int, array{role: string, content: string}> */
    public array $messages = [];

    public string $question = '';

    #[Locked]
    public string $streamRouteBase;

    #[Locked]
    public string $conversationSessionKey;

    public function mount(?string $conversationId = null): void
    {
        $chatbot = $this->chatbot();

        $this->initializeAppearance($chatbot);

        $this->conversationSessionKey = $chatbot->getConversationKey();
        $this->streamRouteBase = route('chatbot.stream');

        $this->initializeConversationSelection($conversationId);
        $this->loadConversation($this->activeStreams());
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

        $activeStreams = $this->activeStreams();

        if ($this->conversationId !== null && array_key_exists($this->conversationId, $activeStreams)) {
            $this->loadConversation($activeStreams);

            return;
        }

        $this->beginStreamingQuestion($message, $activeStreams);
    }

    public function onStreamComplete(string $assistantMessage = ''): void
    {
        $this->appendAssistantMessageIfMissing($assistantMessage);
        $this->clearVisibleStreamState();

        if ($this->conversationId === null) {
            return;
        }

        $activeStreams = $this->activeStreams();
        unset($activeStreams[$this->conversationId]);
        $this->storeActiveStreams($activeStreams);
    }

    public function streamTransport(): array
    {
        $transport = app(StreamTransport::class);

        return [
            'name' => $transport->name(),
            'config' => $transport->clientConfig($this->conversationId ?? ''),
        ];
    }

    public function clearChat(): void
    {
        if ($this->standalone) {
            return;
        }

        $this->cleanupVisibleStream();
        $this->conversationId = null;
        $this->messages = [];
        $this->clearVisibleStreamState();

        session()->put($this->conversationSessionKey, '');
    }

    public function openConversation(string $conversationId): void
    {
        if ($this->standalone || ! Chat::ownedBy($conversationId, auth()->user())) {
            return;
        }

        $this->cleanupVisibleStream();
        $this->conversationId = $conversationId;

        session()->put($this->conversationSessionKey, $conversationId);

        $this->loadConversation($this->activeStreams());
    }

    public function render(): View
    {
        return view('filament-chatbot::chatbot-widget');
    }

    #[Computed]
    public function conversations(): Collection
    {
        return AgentConversation::query()
            ->ownedBy(auth()->user())
            ->orderByDesc('updated_at')
            ->limit(12)
            ->get(['id', 'title', 'updated_at'])
            ->keyBy('id');
    }

    protected function initializeConversationSelection(?string $conversationId): void
    {
        if ($conversationId !== null) {
            abort_unless(Chat::ownedBy($conversationId, auth()->user()), 403);

            $this->standalone = true;
            $this->conversationId = $conversationId;

            return;
        }

        $this->conversationId = $this->currentConversationId(
            session()->get($this->conversationSessionKey),
        );

        session()->put($this->conversationSessionKey, $this->conversationId ?? '');
    }

    protected function loadConversation(array $activeStreams): void
    {
        $this->messages = $this->conversationId
            ? Chat::history($this->conversationId)->all()
            : [];

        $this->clearVisibleStreamState();

        if ($this->conversationId !== null && isset($activeStreams[$this->conversationId])) {
            $this->restoreActiveStream($activeStreams[$this->conversationId]['message']);
        }
    }

    /**
     * @param  array<string, array{message: string}>  $activeStreams
     */
    protected function beginStreamingQuestion(string $message, array $activeStreams): void
    {
        if ($this->conversationId === null) {
            $this->conversationId = Chat::start(auth()->user(), $message)->id;
        }

        $this->messages[] = [
            'role' => MessageRole::User->value,
            'content' => $message,
        ];

        $this->streamMessage = $message;
        $this->isStreaming = true;
        $this->initialStreamingText = '';
        $this->shouldStartStreamRequest = true;

        session()->put($this->conversationSessionKey, $this->conversationId);

        $activeStreams[$this->conversationId] = ['message' => $message];

        $this->storeActiveStreams($activeStreams);
    }

    protected function appendAssistantMessageIfMissing(string $assistantMessage): void
    {
        $last = $this->messages[array_key_last($this->messages)] ?? null;
        $alreadyShown = $last
            && $last['role'] === MessageRole::Assistant->value
            && $last['content'] === $assistantMessage;

        if ($assistantMessage !== '' && ! $alreadyShown) {
            $this->messages[] = [
                'role' => MessageRole::Assistant->value,
                'content' => $assistantMessage,
            ];
        }
    }

    protected function currentConversationId(mixed $conversationId): ?string
    {
        if (is_string($conversationId) && $conversationId !== '' && Chat::ownedBy($conversationId, auth()->user())) {
            return $conversationId;
        }

        return null;
    }

    protected function chatbot(): ChatbotPlugin
    {
        /** @var ChatbotPlugin $plugin */
        $plugin = filament('chatbot');

        return $plugin;
    }
}
