<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id', 'kapso_message_id', 'direction', 'type', 'body',
        'media_url', 'status', 'raw_payload', 'sent_at',
        'ai_tags', 'ai_confidence', 'ai_sentiment', 'ai_suggested_reply', 'ai_handoff_triggered', 'ai_feedback',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'sent_at' => 'datetime',
        'ai_tags' => 'array',
        'ai_handoff_triggered' => 'boolean',
        'ai_feedback' => 'integer',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
