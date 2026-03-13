<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funnels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('funnel_stages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreignId('funnel_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->nullable(); // #ff0000 ou Tailwind class
            $table->integer('order')->default(0);
            $table->timestamps();
        });
        
        // Adicionando foreign key na Conversation para ligar o Lead à etapa
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('funnel_stage_id')->nullable()->constrained('funnel_stages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['funnel_stage_id']);
            $table->dropColumn('funnel_stage_id');
        });
        
        Schema::dropIfExists('funnel_stages');
        Schema::dropIfExists('funnels');
    }
};
