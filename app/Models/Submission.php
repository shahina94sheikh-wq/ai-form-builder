<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Form;

class Submission extends Model
{
    protected $fillable = [
        'form_id',
        'data',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }
}