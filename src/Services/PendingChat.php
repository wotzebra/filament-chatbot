<?php

namespace Wotz\FilamentChatbot\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Context;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Exceptions\AiException;
use Laravel\Ai\Exceptions\InsufficientCreditsException;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use Wotz\FilamentChatbot\Models\AgentConversationMessage;
use Wotz\FilamentChatbot\Support\Chatbot\SseStream;
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

    public function stream(string $message): StreamedResponse
    {
        $events = app(ToolRegistry::class)->usingTools($this->extraTools, function () use ($message): iterable {
            $agent = $this->buildAgent();

            return $agent->stream(
                $message,
                provider: $this->config->provider,
                model: $this->config->model,
            );
        });

        return new StreamedResponse(function () use ($events): void {
            $sse = new SseStream;

            try {
                foreach ($events as $event) {
                    $sse->event((string) $event);
                }
            } catch (Throwable $e) {
                report($e);

                $sse->event(['type' => 'text_delta', 'delta' => $this->friendlyMessageFor($e)]);
            } finally {
                $sse->done();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
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

    public function ask(string $message): string
    {
        return app(ToolRegistry::class)->usingTools($this->extraTools, function () use ($message): string {
            $agent = $this->buildAgent();

            return (string) $agent->ask(
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

        if ($streamedMessage !== '' && $latest && $latest->content === '') {
            $latest->update(['content' => $streamedMessage]);

            return $streamedMessage;
        }

        return $streamedMessage !== '' ? $streamedMessage : (string) $latest?->content;
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

    protected function friendlyMessageFor(Throwable $e): string
    {
        return match (true) {
            $e instanceof RateLimitedException => __('filament-chatbot::chatbot.errors.rate_limited'),
            $e instanceof ProviderOverloadedException => __('filament-chatbot::chatbot.errors.overloaded'),
            $e instanceof InsufficientCreditsException => __('filament-chatbot::chatbot.errors.insufficient_credits'),
            $e instanceof AiException => __('filament-chatbot::chatbot.errors.ai_failed'),
            default => __('filament-chatbot::chatbot.errors.generic'),
        };
    }
}
