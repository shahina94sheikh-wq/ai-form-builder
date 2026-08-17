<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormVersion extends Model
{
    protected $fillable = [
        'form_id',
        'version',
        'schema',
        'created_by',
    ];

    protected $casts = [
        'schema' => 'array',
    ];

    /**
     * Form this version belongs to.
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * User who created this version.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
}   