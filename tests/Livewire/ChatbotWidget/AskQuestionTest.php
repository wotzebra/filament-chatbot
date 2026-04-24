<?php

namespace Wotz\FilamentChatbot\Tests\Livewire\ChatbotWidget;

use Laravel\Ai\Messages\MessageRole;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Wotz\FilamentChatbot\Livewire\ChatbotWidget;
use Wotz\FilamentChatbot\Models\AgentConversation;
use Wotz\FilamentChatbot\Models\AgentConversationMessage;
use Wotz\FilamentChatbot\Tests\TestCase;

class AskQuestionTest extends TestCase
{
    #[Test]
    public function it_does_nothing_when_ask_question_receives_only_whitespace(): void
    {
        Livewire::test(ChatbotWidget::class)
            ->set('question', '   ')
            ->call('askQuestion')
            ->assertSet('question', '')
            ->assertSet('isStreaming', false)
            ->assertSet('conversationId', null);

        $this->assertSame(0, AgentConversation::count());
    }

    #[Test]
    public function it_pushes_the_user_message_and_starts_streaming(): void
    {
        $component = Livewire::test(ChatbotWidget::class)
            ->set('question', $question = fake()->sentence())
            ->call('askQuestion')
            ->assertSet('question', '')
            ->assertSet('isStreaming', true)
            ->assertSet('shouldStartStreamRequest', true)
            ->assertSet('streamMessage', $question);

        $messages = $component->get('messages');

        $this->assertCount(1, $messages);
        $this->assertSame(MessageRole::User->value, $messages[0]['role']);
        $this->assertSame($question, $messages[0]['content']);
    }

    #[Test]
    public function it_creates_a_conversation_record_and_stores_its_id_in_the_session_on_the_first_question(): void
    {
        $component = Livewire::test(ChatbotWidget::class)
            ->set('question', fake()->sentence())
            ->call('askQuestion');

        $this->assertSame(1, AgentConversation::count());
        $this->assertSame($component->get('conversationId'), session()->get($this->conversationKey()));
    }

    #[Test]
    public function it_stores_the_active_stream_state_in_the_session(): void
    {
        $question = fake()->sentence();

        $component = Livewire::test(ChatbotWidget::class)
            ->set('question', $question)
            ->call('askQuestion');

        $this->assertSame(
            [$component->get('conversationId') => ['message' => $question]],
            session()->get($this->activeStreamsKey()),
        );
    }

    #[Test]
    public function it_does_not_persist_the_user_message_directly(): void
    {
        $component = Livewire::test(ChatbotWidget::class)
            ->set('question', fake()->sentence())
            ->call('askQuestion');

        $this->assertSame(
            0,
            AgentConversationMessage::query()->forConversation($component->get('conversationId'))->count(),
        );
    }

    #[Test]
    public function it_reuses_the_same_conversation_on_subsequent_questions(): void
    {
        $component = Livewire::test(ChatbotWidget::class)
            ->set('question', fake()->sentence())
            ->call('askQuestion');

        $firstConversationId = $component->get('conversationId');

        $component
            ->call('onStreamComplete', fake()->sentence())
            ->set('question', fake()->sentence())
            ->call('askQuestion');

        $this->assertSame($firstConversationId, $component->get('conversationId'));
        $this->assertSame(1, AgentConversation::count());
    }

    #[Test]
    public function it_ignores_new_questions_while_a_stream_is_already_active(): void
    {
        Livewire::test(ChatbotWidget::class)
            ->set('isStreaming', true)
            ->set('question', $question = fake()->sentence())
            ->call('askQuestion')
            ->assertSet('question', $question)
            ->assertSet('streamMessage', '')
            ->assertSet('conversationId', null);

        $this->assertSame(0, AgentConversation::count());
    }

    #[Test]
    public function it_ignores_a_duplicate_question_when_the_same_stream_is_already_active_in_the_session(): void
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
            ->create(['content' => '']);

        session()->put($this->conversationKey(), $conversation->id);
        session()->put($this->activeStreamsKey(), [
            $conversation->id => ['message' => $question],
        ]);

        Livewire::test(ChatbotWidget::class)
            ->set('question', $question)
            ->call('askQuestion')
            ->assertSet('conversationId', $conversation->id)
            ->assertSet('isStreaming', true)
            ->assertSet('streamMessage', $question);

        $this->assertSame(
            2,
            AgentConversationMessage::query()->forConversation($conversation->id)->count(),
        );
    }

    #[Test]
    public function it_keeps_messages_serializable_after_loading_history_and_asking_a_new_question(): void
    {
        $conversation = AgentConversation::factory()->create();

        AgentConversationMessage::factory()->user()->for($conversation, 'conversation')->create();
        AgentConversationMessage::factory()->assistant()->for($conversation, 'conversation')->create();

        session()->put($this->conversationKey(), $conversation->id);

        Livewire::test(ChatbotWidget::class)
            ->set('question', $question = fake()->sentence())
            ->call('askQuestion')
            ->assertSet('isStreaming', true)
            ->assertSet('streamMessage', $question);
    }

    #[Test]
    public function clear_chat_starts_a_new_draft_session_without_removing_the_existing_conversation(): void
    {
        $component = Livewire::test(ChatbotWidget::class)
            ->set('question', fake()->sentence())
            ->call('askQuestion')
            ->call('clearChat')
            ->assertSet('conversationId', null)
            ->assertSet('isStreaming', false)
            ->assertSet('streamMessage', '');

        $this->assertSame([], $component->get('messages'));
        $this->assertSame('', session()->get($this->conversationKey()));
        $this->assertSame(1, AgentConversation::count());
    }

    #[Test]
    public function it_can_keep_a_streaming_conversation_open_while_starting_a_second_conversation(): void
    {
        $component = Livewire::test(ChatbotWidget::class)
            ->set('question', $firstQuestion = fake()->sentence())
            ->call('askQuestion')
            ->assertSet('isStreaming', true);

        $firstConversationId = $component->get('conversationId');

        $component
            ->call('clearChat')
            ->set('question', $secondQuestion = fake()->sentence())
            ->call('askQuestion')
            ->assertSet('isStreaming', true);

        $secondConversationId = $component->get('conversationId');

        $this->assertNotSame($firstConversationId, $secondConversationId);
        $this->assertSame(
            [
                $firstConversationId => ['message' => $firstQuestion],
                $secondConversationId => ['message' => $secondQuestion],
            ],
            session()->get($this->activeStreamsKey()),
        );
    }

    #[Test]
    public function it_renders_a_client_side_submit_lock_for_the_chat_input(): void
    {
        Livewire::test(ChatbotWidget::class)
            ->assertSee('submitting: false', false)
            ->assertSee("if (this.submitting || \$wire.\$get('isStreaming')) {", false)
            ->assertSee('@keydown.enter="!$event.shiftKey && ($event.preventDefault(), submit())"', false);
    }
}
