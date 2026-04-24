<?php

namespace Wotz\FilamentChatbot\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Context;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Responses\StreamableAgentResponse;
use RuntimeException;
use Wotz\FilamentChatbot\Support\Chatbot\ChatOverrides;

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

    public function applyOverrides(ChatOverrides $overrides): static
    {
        if ($overrides->agent !== null && $overrides->agent !== '') {
            $this->withAgent($overrides->agent);
        }

        $this->withProvider($overrides->provider);
        $this->withModel($overrides->model);
        $this->withTools($overrides->tools);
        $this->withContext($overrides->context);

        return $this;
    }

    public function streamResponse(string $message): StreamableAgentResponse
    {
        $agent = $this->buildAgent();

        return $agent->stream(
            $message,
            provider: $this->config->provider,
            model: $this->config->model,
        );
    }

    public function buildAgent(): Agent
    {
        $this->applyContext();

        $agentClass = $this->config->agent;

        if (! is_string($agentClass) || $agentClass === '') {
            throw new RuntimeException('No agent configured for Chat. Set filament-chatbot.agent or call ->withAgent() before sending.');
        }

        /** @var Agent $agent */
        $agent = app($agentClass);

        if (method_exists($agent, 'withExtraTools')) {
            $agent->withExtraTools($this->extraTools);
        }

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
