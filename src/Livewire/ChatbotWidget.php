<?php

namespace Wotz\FilamentChatbot\Livewire;

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

    /** @var array<int, array{role: string, content: string}> */
    public array $messages = [];

    /** @var array<int, string> */
    public array $openConversationIds = [];

    /** @var array<int, array{id: string, title: string, preview: string, updated_at_human: string, is_active: bool, is_streaming: bool}> */
    public array $conversationList = [];

    public string $question = '';

    public bool $isStreaming = false;

    public string $streamMessage = '';

    public string $initialStreamingText = '';

    public bool $shouldStartStreamRequest = false;

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
        $activeStreams = $this->pruneCompletedStreams(
            $this->resolveActiveStreams(session()->get($this->activeStreamSessionKey())),
        );
        $this->openConversationIds = $this->resolveOpenConversationIds(
            session()->get($this->openConversationsSessionKey(), []),
        );

        if ($conversationId !== null) {
            abort_unless(Chat::ownedBy($conversationId, auth()->user()), 403);

            $this->standalone = true;
            $this->conversationId = $conversationId;
        } else {
            $this->conversationId = $this->resolveCurrentConversationId(
                session()->get($this->conversationSessionKey()),
            );

            if ($this->conversationId !== null) {
                $this->rememberOpenConversation($this->conversationId);
            }

            session()->put($this->conversationSessionKey(), $this->conversationId ?? '');
        }

        $this->loadConversation($activeStreams);
        $this->syncConversationList($activeStreams);
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

        $activeStreams = $this->activeStreams();
        $resolvedConversationId = $this->conversationId
            ?? $this->resolveCurrentConversationId(session()->get($this->conversationSessionKey()));

        if ($resolvedConversationId !== null && array_key_exists($resolvedConversationId, $activeStreams)) {
            $this->conversationId = $resolvedConversationId;
            $this->rememberOpenConversation($resolvedConversationId);
            session()->put($this->conversationSessionKey(), $resolvedConversationId);
            $this->loadConversation($activeStreams);
            $this->syncConversationList($activeStreams);

            return;
        }

        if (! $this->conversationId) {
            $this->conversationId = Chat::start(auth()->user(), $message)->id;
        }

        Chat::addUserMessage($this->conversationId, auth()->user(), $message);

        $this->messages[] = [
            'role' => MessageRole::User->value,
            'content' => $message,
        ];
        $this->streamMessage = $message;
        $this->isStreaming = true;
        $this->initialStreamingText = '';
        $this->shouldStartStreamRequest = true;

        $this->rememberOpenConversation($this->conversationId);
        session()->put($this->conversationSessionKey(), $this->conversationId);

        $activeStreams[$this->conversationId] = ['message' => $message];
        $this->storeActiveStreams($activeStreams);
        $this->syncConversationList($activeStreams);
    }

    public function onStreamComplete(string $assistantMessage = '', ?string $conversationId = null): void
    {
        $resolvedConversationId = $conversationId ?: $this->conversationId;
        $resolved = $assistantMessage;

        if ($resolvedConversationId === $this->conversationId) {
            $last = $this->messages[array_key_last($this->messages)] ?? null;
            $alreadyShown = $last
                && $last['role'] === MessageRole::Assistant->value
                && $last['content'] === $resolved;

            if ($resolved !== '' && ! $alreadyShown) {
                $this->messages[] = [
                    'role' => MessageRole::Assistant->value,
                    'content' => $resolved,
                ];
            }

            $this->isStreaming = false;
            $this->streamMessage = '';
            $this->initialStreamingText = '';
            $this->shouldStartStreamRequest = false;
        }

        $activeStreams = $this->activeStreams();

        if ($resolvedConversationId !== null) {
            unset($activeStreams[$resolvedConversationId]);
        }

        $this->storeActiveStreams($activeStreams);
        $this->syncConversationList($activeStreams);
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

        $this->cleanupVisibleStream();
        $this->conversationId = null;
        $this->messages = [];
        $this->isStreaming = false;
        $this->streamMessage = '';
        $this->initialStreamingText = '';
        $this->shouldStartStreamRequest = false;

        session()->put($this->conversationSessionKey(), '');
        $this->syncConversationList($this->activeStreams());
    }

    public function openConversation(string $conversationId): void
    {
        if ($this->standalone || ! Chat::ownedBy($conversationId, auth()->user())) {
            return;
        }

        $this->cleanupVisibleStream();
        $this->conversationId = $conversationId;
        session()->put($this->conversationSessionKey(), $conversationId);

        $activeStreams = $this->pruneCompletedStreams($this->activeStreams());

        $this->rememberOpenConversation($conversationId);
        $this->loadConversation($activeStreams);
        $this->syncConversationList($activeStreams);
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

    protected function loadConversation(array $activeStreams): void
    {
        $this->messages = $this->conversationId
            ? Chat::history($this->conversationId)
            : [];
        $this->isStreaming = false;
        $this->streamMessage = '';
        $this->initialStreamingText = '';

        if ($this->conversationId === null) {
            return;
        }

        if (isset($activeStreams[$this->conversationId])) {
            $this->restoreActiveStream($activeStreams[$this->conversationId]['message']);
        }
    }

    protected function resolveCurrentConversationId(mixed $conversationId): ?string
    {
        if (is_string($conversationId)) {
            if ($conversationId === '') {
                return null;
            }

            if (Chat::ownedBy($conversationId, auth()->user())) {
                return $conversationId;
            }
        }

        return $this->openConversationIds[0] ?? null;
    }

    /**
     * @return array<string, array{message: string}>
     */
    protected function resolveActiveStreams(mixed $activeStreams): array
    {
        if (! is_array($activeStreams)) {
            return [];
        }

        $resolvedConversationIds = Chat::ownedConversationIds(array_keys($activeStreams), auth()->user());
        $resolvedActiveStreams = [];

        foreach ($resolvedConversationIds as $conversationId) {
            $message = trim((string) ($activeStreams[$conversationId]['message'] ?? ''));

            if ($message === '') {
                continue;
            }

            $resolvedActiveStreams[$conversationId] = ['message' => $message];
        }

        $this->storeActiveStreams($resolvedActiveStreams);

        return $resolvedActiveStreams;
    }

    /**
     * @param  array<int, string>|mixed  $conversationIds
     * @return array<int, string>
     */
    protected function resolveOpenConversationIds(mixed $conversationIds): array
    {
        if (! is_array($conversationIds)) {
            return [];
        }

        $resolvedConversationIds = Chat::ownedConversationIds(
            array_values(array_filter($conversationIds, 'is_string')),
            auth()->user(),
        );

        session()->put($this->openConversationsSessionKey(), $resolvedConversationIds);

        return $resolvedConversationIds;
    }

    /**
     * @param  array<string, array{message: string}>  $activeStreams
     * @return array<string, array{message: string}>
     */
    protected function pruneCompletedStreams(array $activeStreams): array
    {
        $pendingConversationIds = Chat::pendingConversationIds(array_keys($activeStreams));

        $resolvedActiveStreams = array_intersect_key($activeStreams, array_flip($pendingConversationIds));
        $this->storeActiveStreams($resolvedActiveStreams);

        return $resolvedActiveStreams;
    }

    protected function restoreActiveStream(string $message): void
    {
        $last = end($this->messages) ?: null;

        if ($last !== null && $last['role'] === MessageRole::Assistant->value) {
            $popped = array_pop($this->messages);
            $this->initialStreamingText = $popped['content'];
            $last = end($this->messages) ?: null;
        }

        $hasPendingUserMessage = $last !== null
            && $last['role'] === MessageRole::User->value
            && $last['content'] === $message;

        if (! $hasPendingUserMessage) {
            $this->messages[] = [
                'role' => MessageRole::User->value,
                'content' => $message,
            ];
        }

        $this->streamMessage = $message;
        $this->isStreaming = true;
        $this->shouldStartStreamRequest = false;
    }

    protected function conversationSessionKey(): string
    {
        return $this->chatbot()->getConversationKey();
    }

    protected function openConversationsSessionKey(): string
    {
        return $this->conversationSessionKey() . '_open';
    }

    protected function activeStreamSessionKey(): string
    {
        return $this->chatbot()->getActiveStreamsSessionKey();
    }

    /**
     * @return array<string, array{message: string}>
     */
    protected function activeStreams(): array
    {
        return $this->resolveActiveStreams(session()->get($this->activeStreamSessionKey()));
    }

    /**
     * @param  array<string, array{message: string}>  $activeStreams
     */
    protected function storeActiveStreams(array $activeStreams): void
    {
        session()->put($this->activeStreamSessionKey(), $activeStreams);
    }

    protected function rememberOpenConversation(string $conversationId): void
    {
        $this->openConversationIds = array_values(array_unique([
            $conversationId,
            ...array_filter(
                $this->openConversationIds,
                fn (string $openConversationId): bool => $openConversationId !== $conversationId,
            ),
        ]));

        session()->put($this->openConversationsSessionKey(), $this->openConversationIds);
    }

    /**
     * @param  array<string, array{message: string}>  $activeStreams
     */
    protected function syncConversationList(array $activeStreams): void
    {
        $conversationIds = array_values(array_unique([
            ...Chat::recentConversationIds(auth()->user()),
            ...$this->openConversationIds,
            ...($this->conversationId ? [$this->conversationId] : []),
        ]));
        $summaries = Chat::summariesFor($conversationIds);

        $this->conversationList = array_values(array_map(
            fn (string $conversationId): array => [
                ...$summaries[$conversationId],
                'is_active' => $conversationId === $this->conversationId,
                'is_streaming' => array_key_exists($conversationId, $activeStreams),
            ],
            array_values(array_filter(
                $conversationIds,
                fn (string $conversationId): bool => array_key_exists($conversationId, $summaries),
            )),
        ));
    }

    protected function cleanupVisibleStream(): void
    {
        if (! $this->isStreaming || $this->conversationId === null) {
            return;
        }

        $this->dispatch('chatbot-stream-cleanup', conversationId: $this->conversationId);
    }

    protected function chatbot(): ChatbotPlugin
    {
        /** @var ChatbotPlugin $plugin */
        $plugin = filament('chatbot');

        return $plugin;
    }
}
