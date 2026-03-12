<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('action_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Quem realizou a ação (null se for o sistema)
            
            $table->string('action_type'); // Ex: 'ticket_assigned', 'message_sent', 'bot_started'
            $table->string('entity_type')->nullable(); // Ex: 'Conversation', 'BotInstance'
            $table->unsignedBigInteger('entity_id')->nullable(); // O ID do item afetado
            
            $table->text('description')->nullable(); // Descrição humana
            $table->json('meta_data')->nullable(); // JSON com detalhes do payload ou changes
            
            $table->ipAddress('ip_address')->nullable();
            
            $table->timestamps();
            
            $table->index(['tenant_id', 'entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_logs');
    }
};
