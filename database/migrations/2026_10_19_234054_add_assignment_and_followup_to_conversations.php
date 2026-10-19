<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete()->after('lead_stage');
            $table->timestamp('followed_up_at')->nullable()->after('last_message_at');
            $table->timestamp('last_inbound_at')->nullable()->after('last_message_at');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropColumn(['followed_up_at', 'last_inbound_at']);
        });
    }
};
