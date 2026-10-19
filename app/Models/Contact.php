<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = ['phone', 'name', 'avatar', 'kapso_contact_id', 'metadata', 'last_seen_at'];

    protected $casts = [
        'metadata' => 'array',
        'last_seen_at' => 'datetime',
    ];

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
