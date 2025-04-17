<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('scheduled_notifications', function (Blueprint $table) {
            $table->id();
            
            // Who to notify (one of these will be filled)
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->onDelete('cascade');
            $table->foreignId('topic_id')->nullable()->constrained('topics')->onDelete('cascade');
            $table->boolean('send_to_all')->default(false);
            
            // Content (either direct content or reference to a message template)
            $table->foreignId('message_id')->nullable()->constrained('messages')->onDelete('cascade');
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('url')->nullable();
            $table->string('icon_path')->nullable();
            
            // Scheduling info
            $table->timestamp('scheduled_at');
            $table->timestamp('sent_at')->nullable();
            
            // Status tracking
            $table->enum('status', ['pending', 'processing', 'sent', 'failed'])->default('pending');
            $table->text('error')->nullable();
            $table->integer('attempts')->default(0);
            
            // Metadata
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['status', 'scheduled_at']);
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_notifications');
    }
};