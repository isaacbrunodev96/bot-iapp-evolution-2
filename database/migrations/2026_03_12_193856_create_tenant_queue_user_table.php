<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_queue_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_queue_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['tenant_queue_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_queue_user');
    }
};
