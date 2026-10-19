<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->json('ai_tags')->nullable()->after('status');
            $table->decimal('ai_confidence', 5, 2)->nullable()->after('ai_tags');
            $table->text('ai_suggested_reply')->nullable()->after('ai_confidence');
            $table->boolean('ai_handoff_triggered')->default(false)->after('ai_suggested_reply');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['ai_tags', 'ai_confidence', 'ai_suggested_reply', 'ai_handoff_triggered']);
        });
    }
};
