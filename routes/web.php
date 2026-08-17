<?php

use Illuminate\Support\Facades\Route;

use App\Livewire\PublicForm;
use App\Livewire\FormBuilder;
use App\Livewire\FormWizard;
use App\Livewire\SubmissionList;
use App\Livewire\AiFormGenerator;
use App\Livewire\FormList;
use App\Livewire\AiFormEdit;
use App\Livewire\FormVersionHistory;
use App\Livewire\FormImportUpload;
use App\Livewire\FormImportPreview;

use App\Http\Controllers\SubmissionController;


Route::get('/', function () {
    return view('welcome');
});


/*
|--------------------------------------------------------------------------
| Forms
|--------------------------------------------------------------------------
*/

Route::get(
    '/forms',
    FormList::class
)->name('forms.index');


/*
|--------------------------------------------------------------------------
| Create Manual Form
|--------------------------------------------------------------------------
*/

Route::get(
    '/forms/create',
    FormWizard::class
)->name('forms.create');


/*
|--------------------------------------------------------------------------
| Create AI Form
|--------------------------------------------------------------------------
*/

Route::get(
    '/forms/ai',
    AiFormGenerator::class
)->name('forms.ai');


/*
|--------------------------------------------------------------------------
| Form Builder
|--------------------------------------------------------------------------
*/

Route::get(
    '/forms/{form:slug}/builder',
    FormBuilder::class
)->name('forms.builder');


/*
|--------------------------------------------------------------------------
| AI Edit
|--------------------------------------------------------------------------
*/

Route::get(
    '/forms/{form:slug}/ai-edit',
    AiFormEdit::class
)->name('forms.ai.edit');


/*
|--------------------------------------------------------------------------
| Version History
|--------------------------------------------------------------------------
*/

Route::get(
    '/forms/{form:slug}/versions',
    FormVersionHistory::class
)->name('forms.versions');


/*
|--------------------------------------------------------------------------
| Public Form
|--------------------------------------------------------------------------
*/

Route::get(
    '/f/{form:slug}',
    PublicForm::class
)->name('forms.public');


/*
|--------------------------------------------------------------------------
| Submissions
|--------------------------------------------------------------------------
*/

Route::get(
    '/forms/{form:slug}/submissions',
    SubmissionList::class
)->name('forms.submissions');


/*
|--------------------------------------------------------------------------
| Export Submissions
|--------------------------------------------------------------------------
*/

Route::get(
    '/forms/{form:slug}/submissions/csv',
    [SubmissionController::class, 'export']
)->name('forms.submissions.csv');

/*
|--------------------------------------------------------------------------
| File Import
|--------------------------------------------------------------------------
*/

Route::get(
    '/forms/import',
    FormImportUpload::class
)->name('forms.import');


Route::get(
    '/forms/import/{import}/preview',
    \App\Livewire\FormImportPreview::class
)->name('forms.import.preview');