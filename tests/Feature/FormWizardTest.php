<?php

namespace Tests\Feature;

use App\Livewire\FormBuilder;
use App\Livewire\FormWizard;
use App\Models\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FormWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_builder_changes_are_saved_before_the_wizard_advances_and_publishes(): void
    {
        $wizard = Livewire::test(FormWizard::class)
            ->set('title', 'Volunteer Signup')
            ->call('nextStep')
            ->assertSet('step', 2);

        $form = Form::firstOrFail();

        $wizard->call('nextStep')
            ->assertDispatched('wizard-save-builder')
            ->assertSet('step', 2);

        Livewire::test(FormBuilder::class, ['form' => $form])
            ->call('addField', 'text')
            ->call('saveForWizard')
            ->assertHasNoErrors()
            ->assertDispatched('wizard-builder-saved');

        $wizard->call('completeBuilderStep')
            ->assertSet('step', 3)
            ->call('nextStep')
            ->assertSet('step', 4)
            ->call('publishForm')
            ->assertRedirect(route('forms.public', ['form' => $form->slug]));

        $form->refresh();

        $this->assertSame('published', $form->status);
        $this->assertCount(1, $form->schema['sections'][0]['fields']);
    }
}
