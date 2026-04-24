<?php

namespace Wotz\FilamentChatbot\Tests\Livewire\ChatbotWidget;

use Laravel\Ai\Messages\MessageRole;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Wotz\FilamentChatbot\Livewire\ChatbotWidget;
use Wotz\FilamentChatbot\Models\AgentConversation;
use Wotz\FilamentChatbot\Models\AgentConversationMessage;
use Wotz\FilamentChatbot\Tests\TestCase;

class StreamTest extends TestCase
{
    #[Test]
    public function on_stream_complete_adds_an_assistant_message_and_clears_stream_state(): void
    {
        $component = Livewire::test(ChatbotWidget::class)
            ->set('question', fake()->sentence())
            ->call('askQuestion')
            ->call('onStreamComplete', $reply = fake()->sentence())
            ->assertSet('isStreaming', false)
            ->assertSet('streamMessage', '');

        $messages = $component->get('messages');
        $last = $messages[array_key_last($messages)];

        $this->assertSame(MessageRole::Assistant->value, $last['role']);
        $this->assertSame($reply, $last['content']);
    }

    #[Test]
    public function on_stream_complete_forgets_the_active_stream_state(): void
    {
        Livewire::test(ChatbotWidget::class)
            ->set('question', fake()->sentence())
            ->call('askQuestion')
            ->call('onStreamComplete', fake()->sentence());

        $this->assertSame([], session()->get($this->activeStreamsKey()));
    }

    #[Test]
    public function on_stream_complete_does_not_duplicate_the_assistant_message_when_called_twice(): void
    {
        $component = Livewire::test(ChatbotWidget::class)
            ->set('question', fake()->sentence())
            ->call('askQuestion')
            ->call('onStreamComplete', $reply = fake()->sentence())
            ->call('onStreamComplete', $reply);

        $assistantMessages = collect($component->get('messages'))
            ->filter(fn ($message) => $message['role'] === MessageRole::Assistant->value);

        $this->assertCount(1, $assistantMessages);
    }

    #[Test]
    public function on_stream_complete_fetches_the_assistant_message_from_the_database_when_none_is_passed(): void
    {
        $conversation = AgentConversation::factory()->create();

        $message = AgentConversationMessage::factory()
            ->assistant()
            ->for($conversation, 'conversation')
            ->create();

        session()->put($this->conversationKey(), $conversation->id);

        $component = Livewire::test(ChatbotWidget::class)->call('onStreamComplete', '');

        $messages = $component->get('messages');

        $this->assertSame($message['content'], $messages[array_key_last($messages)]['content']);
    }

    #[Test]
    public function on_stream_complete_appends_the_streamed_reply_to_the_local_messages(): void
    {
        $conversation = AgentConversation::factory()->create();

        session()->put($this->conversationKey(), $conversation->id);

        $component = Livewire::test(ChatbotWidget::class)
            ->call('onStreamComplete', $reply = fake()->sentence());

        $messages = $component->get('messages');
        $last = $messages[array_key_last($messages)];

        $this->assertSame($reply, $last['content']);
        $this->assertSame(MessageRole::Assistant->value, $last['role']);
    }

    #[Test]
    public function it_restores_an_active_stream_across_navigation_and_keeps_listening_on_the_same_conversation(): void
    {
        [$conversation, $question] = $this->seedActiveStream();

        $component = Livewire::test(ChatbotWidget::class)
            ->assertSet('conversationId', $conversation->id)
            ->assertSet('isStreaming', true)
            ->assertSet('shouldStartStreamRequest', false)
            ->assertSet('streamMessage', $question)
            ->assertSet('initialStreamingText', '');

        $messages = $component->get('messages');

        $this->assertCount(1, $messages);
        $this->assertSame(MessageRole::User->value, $messages[0]['role']);
        $this->assertSame($question, $messages[0]['content']);
    }

    #[Test]
    public function it_does_not_duplicate_the_pending_user_message_when_restoring_an_active_stream_with_persisted_history(): void
    {
        [, $question] = $this->seedActiveStream();

        $component = Livewire::test(ChatbotWidget::class)
            ->assertSet('isStreaming', true)
            ->assertSet('streamMessage', $question);

        $this->assertCount(1, $component->get('messages'));
    }

    #[Test]
    public function it_restores_the_partial_assistant_response_into_the_streaming_bubble(): void
    {
        [, $question] = $this->seedActiveStream($partialAnswer = fake()->sentence());

        $component = Livewire::test(ChatbotWidget::class)
            ->assertSet('isStreaming', true)
            ->assertSet('streamMessage', $question)
            ->assertSet('initialStreamingText', $partialAnswer);

        $messages = $component->get('messages');

        $this->assertCount(1, $messages);
        $this->assertSame(MessageRole::User->value, $messages[0]['role']);
        $this->assertSame($question, $messages[0]['content']);
    }

    #[Test]
    public function it_renders_streaming_guard_logic_so_a_remounted_widget_skips_starting_a_duplicate_request(): void
    {
        Livewire::test(ChatbotWidget::class)
            ->set('question', fake()->sentence())
            ->call('askQuestion')
            ->assertSet('isStreaming', true)
            ->assertSee('if (window.__filamentChatbotStreams[this.streamKey]) {', false)
            ->assertSee('shouldStartStreamRequest: true,', false)
            ->assertSee('window.__filamentChatbotStreams[this.streamKey] = {', false)
            ->assertSee('cleanup: () => this.cleanup(),', false);
    }

    #[Test]
    public function it_renders_restored_streams_without_restarting_the_websocket_request(): void
    {
        $this->seedActiveStream();

        Livewire::test(ChatbotWidget::class)
            ->assertSee('shouldStartStreamRequest: false,', false)
            ->assertSee('if (! this.shouldStartStreamRequest) {', false);
    }

    /**
     * @return array{0: AgentConversation, 1: string}
     */
    private function seedActiveStream(string $partialAssistantAnswer = ''): array
    {
        $conversation = AgentConversation::factory()->create();
        $question = fake()->sentence();

        AgentConversationMessage::factory()
            ->user()
            ->for($conversation, 'conversation')
            ->create(['content' => $question]);

        AgentConversationMessage::factory()
            ->assistant()
            ->for($conversation, 'conversation')
            ->create(['content' => $partialAssistantAnswer]);

        session()->put($this->conversationKey(), $conversation->id);
        session()->put($this->activeStreamsKey(), [
            $conversation->id => ['message' => $question],
        ]);

        return [$conversation, $question];
    }
}
