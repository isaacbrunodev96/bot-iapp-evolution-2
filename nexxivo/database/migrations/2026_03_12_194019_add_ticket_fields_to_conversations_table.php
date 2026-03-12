<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('conversations', 'status')) {
                $table->string('status')->default('pending')->after('tenant_id'); // pending, open, closed
            }
            if (!Schema::hasColumn('conversations', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null')->after('status');
            }
            if (!Schema::hasColumn('conversations', 'tenant_queue_id')) {
                $table->foreignId('tenant_queue_id')->nullable()->constrained('tenant_queues')->onDelete('set null')->after('user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (Schema::hasColumn('conversations', 'user_id')) {
                // Not dropping foreign and column just in case it belongs to the previous original implementation
            }
            if (Schema::hasColumn('conversations', 'tenant_queue_id')) {
                $table->dropForeign(['tenant_queue_id']);
                $table->dropColumn(['tenant_queue_id']);
            }
            if (Schema::hasColumn('conversations', 'status')) {
                $table->dropColumn(['status']);
            }
        });
    }
};
