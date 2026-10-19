<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = ['contact_id', 'phone_number_id', 'status', 'unread_count', 'last_message_at', 'last_inbound_at', 'followed_up_at', 'ai_mode', 'ai_handoff', 'handoff_reason', 'ai_tags', 'lead_score', 'lead_stage', 'assigned_to'];

    protected $casts = [
        'last_message_at' => 'datetime',
        'last_inbound_at' => 'datetime',
        'followed_up_at' => 'datetime',
        'ai_handoff' => 'boolean',
        'ai_tags' => 'array',
    ];

    public function assignee()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }
}
