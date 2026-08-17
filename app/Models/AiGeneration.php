<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Form;

class AiGeneration extends Model
{
    protected $fillable = [
        'form_id',
        'prompt',
        'mode',
        'status',
        'model',
        'input_tokens',
        'output_tokens',
        'latency_ms',
        'result_schema',
        'error',
    ];

    protected $casts = [
        'result_schema' => 'array',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
}