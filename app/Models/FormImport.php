<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormImport extends Model
{
    protected $fillable = [
        'user_id',
        'filename',
        'disk',
        'type',
        'status',
        'parsed_data',
        'schema',
        'errors',
    ];

    protected $casts = [
        'parsed_data' => 'array',
        'schema' => 'array',
        'errors' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}