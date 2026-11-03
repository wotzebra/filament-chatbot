<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agent_conversations')) {
            Schema::create('agent_conversations', function (Blueprint $table) {
                $table->string('id', 36)->primary();
                $table->string('participant_type')->nullable();
                $table->unsignedBigInteger('participant_id')->nullable();
                $table->string('title');
                $table->timestamps();

                $table->index(['participant_type', 'participant_id', 'updated_at'], 'participant_updated_at_index');
            });
        }

        if (! Schema::hasTable('agent_conversation_messages')) {
            Schema::create('agent_conversation_messages', function (Blueprint $table) {
                $table->string('id', 36)->primary();
                $table->string('conversation_id', 36)->index();
                $table->string('participant_type')->nullable();
                $table->unsignedBigInteger('participant_id')->nullable();
                $table->string('agent');
                $table->string('role', 25);
                $table->longText('content');
                $table->longText('attachments');
                $table->longText('steps');
                $table->longText('usage');
                $table->longText('meta');
                $table->string('status', 25);
                $table->timestamps();

                $table->index(['conversation_id', 'participant_type', 'participant_id', 'updated_at'], 'conversation_index');
                $table->index(['participant_type', 'participant_id', 'agent'], 'participant_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_conversation_messages');
        Schema::dropIfExists('agent_conversations');
    }
};
