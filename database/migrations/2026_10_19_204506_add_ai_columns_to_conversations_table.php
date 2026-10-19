<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('ai_mode')->default('auto')->after('status'); // off, assist, auto
            $table->boolean('ai_handoff')->default(false)->after('ai_mode');
            $table->string('handoff_reason')->nullable()->after('ai_handoff');
            $table->json('ai_tags')->nullable()->after('handoff_reason');
            $table->unsignedTinyInteger('lead_score')->nullable()->after('ai_tags');
            $table->string('lead_stage')->nullable()->after('lead_score'); // new, qualified, negotiating, won, lost
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['ai_mode', 'ai_handoff', 'handoff_reason', 'ai_tags', 'lead_score', 'lead_stage']);
        });
    }
};
