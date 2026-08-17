<?php

namespace App\Services;

use App\Models\Form;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FormService
{
    public function __construct(
        protected FormSchemaValidator $validator
    ) {}

    public function create(
        string $title,
        array $schema,
        ?int $userId = null
    ): Form {

        $schema = $this->validator->validate($schema);

        return DB::transaction(function () use (
            $title,
            $schema,
            $userId
        ) {

            $form = Form::create([
                'user_id' => $userId,
                'title' => $title,
                'slug' => Str::slug($title) . '-' . Str::lower(Str::random(6)),
                'schema' => $schema,
                'status' => 'draft',
            ]);

            $form->versions()->create([
                'version' => 1,
                'schema' => $schema,
                'created_by' => $userId,
            ]);

            return $form;
        });
    }

    public function update(
        Form $form,
        array $schema,
        ?int $userId = null
    ): Form {

        $schema = $this->validator->validate($schema);

        return DB::transaction(function () use (
            $form,
            $schema,
            $userId
        ) {

            $version = $form->versions()->max('version') ?? 0;

            $form->update([
                'schema' => $schema,
                'title' => $schema['title'],
            ]);

            $form->versions()->create([
                'version' => $version + 1,
                'schema' => $schema,
                'created_by' => $userId,
            ]);

            return $form->refresh();
        });
    }
}