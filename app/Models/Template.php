<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    use HasFactory;

    protected $fillable = ['kapso_template_id', 'name', 'language', 'category', 'status', 'components'];

    protected $casts = [
        'components' => 'array',
    ];

    public function broadcasts(): HasMany
    {
        return $this->hasMany(Broadcast::class);
    }
}
