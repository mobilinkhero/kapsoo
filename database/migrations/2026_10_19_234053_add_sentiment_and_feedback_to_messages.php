<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->decimal('ai_sentiment', 4, 2)->nullable()->after('ai_confidence');
            $table->tinyInteger('ai_feedback')->nullable()->after('ai_handoff_triggered');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['ai_sentiment', 'ai_feedback']);
        });
    }
};
