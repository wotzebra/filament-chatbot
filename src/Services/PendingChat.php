<?php

namespace Wotz\FilamentChatbot\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Context;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use RuntimeException;
use Wotz\FilamentChatbot\Models\AgentConversationMessage;
use Wotz\FilamentChatbot\Support\Chatbot\ToolRegistry;

class PendingChat
{
    protected ?Authenticatable $user = null;

    protected ?string $context = null;

    /** @var array<int, mixed> */
    protected array $extraTools = [];

    public function __construct(
        protected ChatConfig $config,
        protected string $conversationId,
    ) {}

    public function as(?Authenticatable $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function withContext(?string $context): static
    {
        $this->context = $context;

        return $this;
    }

    public function withAgent(string $agent): static
    {
        $this->config->agent = $agent;

        return $this;
    }

    public function withProvider(string|array|null $provider): static
    {
        $this->config->provider = $provider;

        return $this;
    }

    public function withModel(?string $model): static
    {
        $this->config->model = $model;

        return $this;
    }

    /**
     * @param  array<int, mixed>  $tools
     */
    public function withTools(array $tools): static
    {
        $this->extraTools = $tools;

        return $this;
    }

    /**
     * Apply plugin-derived overrides in one shot. Keys are optional; missing
     * keys leave the existing config untouched.
     *
     * @param  array{agent?: string, provider?: string|array|null, model?: ?string, tools?: array<int, mixed>, context?: ?string}  $overrides
     */
    public function applyOverrides(array $overrides): static
    {
        if (! empty($overrides['agent'])) {
            $this->withAgent($overrides['agent']);
        }

        if (array_key_exists('provider', $overrides)) {
            $this->withProvider($overrides['provider']);
        }

        if (array_key_exists('model', $overrides)) {
            $this->withModel($overrides['model']);
        }

        if (! empty($overrides['tools'])) {
            $this->withTools($overrides['tools']);
        }

        if (array_key_exists('context', $overrides)) {
            $this->withContext($overrides['context']);
        }

        return $this;
    }

    public function streamEvents(string $message): iterable
    {
        return app(ToolRegistry::class)->usingTools($this->extraTools, function () use ($message): iterable {
            $agent = $this->buildAgent();

            return $agent->stream(
                $message,
                provider: $this->config->provider,
                model: $this->config->model,
            );
        });
    }

    public function finalize(string $streamedMessage): string
    {
        $latest = AgentConversationMessage::query()
            ->forConversation($this->conversationId)
            ->assistant()
            ->latest('created_at')
            ->first();

        if ($latest === null) {
            return $streamedMessage;
        }

        $resolved = $streamedMessage !== '' ? $streamedMessage : (string) $latest->content;

        $latest->update([
            'content' => $resolved,
            'meta' => [
                ...($latest->meta ?? []),
                'pending' => false,
            ],
        ]);

        return $resolved;
    }

    protected function buildAgent(): Agent
    {
        $this->applyContext();

        $agentClass = $this->config->agent;

        if (! is_string($agentClass) || $agentClass === '') {
            throw new RuntimeException('No agent configured for Chat. Set filament-chatbot.agent or call ->withAgent() before sending.');
        }

        /** @var Agent $agent */
        $agent = new $agentClass;

        if ($agent instanceof Conversational && method_exists($agent, 'continue')) {
            $agent->continue($this->conversationId, as: $this->user);
        }

        return $agent;
    }

    protected function applyContext(): void
    {
        Context::addHidden('chatbot.context', $this->context);
    }
}
