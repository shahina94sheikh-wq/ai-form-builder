<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Submission;
use App\Models\AiGeneration;

class Form extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'schema',
        'settings',
        'status',
        'published_at',
        'ai_generated',
    ];

    protected $casts = [
        'schema' => 'array',
        'settings' => 'array',
        'published_at' => 'datetime',
        'ai_generated' => 'boolean',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(FormVersion::class);
    }   

    public function nextVersionNumber(): int
    {
        return (
            $this->versions()->max('version') ?? 0
        ) + 1;
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function aiGenerations()
    {
        return $this->hasMany(AiGeneration::class);
    }
}