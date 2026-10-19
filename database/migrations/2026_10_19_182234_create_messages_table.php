<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('kapso_message_id')->nullable()->unique();
            $table->enum('direction', ['inbound', 'outbound'])->index();
            $table->string('type')->default('text');
            $table->text('body')->nullable();
            $table->string('media_url')->nullable();
            $table->string('status')->default('sent')->index();
            $table->json('raw_payload')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};