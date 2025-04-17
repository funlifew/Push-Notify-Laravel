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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId("subscription_id")->constrained('subscriptions')->onDelete("cascade");
            $table->boolean("status");
            $table->string("title")->nullable(true);
            $table->text("body")->nullable(true);
            $table->string("icon_path")->nullable(true);
            $table->text("url")->nullable(true);
            $table->foreignId("message_id")->nullable(true)->constrained("messages")->onDelete("cascade");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
