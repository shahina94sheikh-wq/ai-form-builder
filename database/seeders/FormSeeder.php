<?php

namespace Database\Seeders;

use App\Models\Form;
use Illuminate\Database\Seeder;

class FormSeeder extends Seeder
{
    public function run(): void
    {
        Form::updateOrCreate(
            [
                'slug' => 'internship-application-X92KQ',
            ],
            [
                'title' => 'Internship Application',

                'schema' => [
                    'version' => '1.0',

                    'title' => 'Internship Application',

                    'description' =>
                        'Please complete this internship application form.',

                    'settings' => [
                        'success_message' =>
                            'Thank you for your application.',
                    ],

                    'sections' => [

                        [
                            'id' => 'section_personal',
                            'title' => 'Personal Information',

                            'fields' => [

                                [
                                    'id' => 'field_full_name',
                                    'key' => 'full_name',
                                    'type' => 'text',
                                    'label' => 'Full Name',
                                    'placeholder' =>
                                        'Enter your full name',
                                    'help' => '',
                                    'default' => '',
                                    'required' => true,
                                    'validation' => [],
                                ],

                                [
                                    'id' => 'field_email',
                                    'key' => 'email',
                                    'type' => 'email',
                                    'label' => 'Email Address',
                                    'placeholder' =>
                                        'Enter your email address',
                                    'help' => '',
                                    'default' => '',
                                    'required' => true,
                                    'validation' => [],
                                ],

                                [
                                    'id' => 'field_phone',
                                    'key' => 'phone',
                                    'type' => 'phone',
                                    'label' => 'Phone Number',
                                    'placeholder' =>
                                        'Enter your phone number',
                                    'help' => '',
                                    'default' => '',
                                    'required' => false,
                                    'validation' => [
                                        'max' => 30,
                                    ],
                                ],

                            ],
                        ],

                        [
                            'id' => 'section_education',
                            'title' => 'Education',

                            'fields' => [

                                [
                                    'id' => 'field_education',
                                    'key' => 'education',
                                    'type' => 'textarea',
                                    'label' => 'Education History',
                                    'placeholder' =>
                                        'Enter your education details',
                                    'help' =>
                                        'Include degree, institution and graduation year.',
                                    'default' => '',
                                    'required' => true,
                                    'validation' => [
                                        'min' => 1,
                                        'max' => 1000,
                                    ],
                                ],

                            ],
                        ],

                        [
                            'id' => 'section_skills',
                            'title' => 'Skills',

                            'fields' => [

                                [
                                    'id' => 'field_skills',
                                    'key' => 'skills',
                                    'type' => 'checkbox',
                                    'label' => 'Skills',
                                    'placeholder' => '',
                                    'help' => 'Select your skills.',
                                    'default' => [],
                                    'required' => true,

                                    'options' => [
                                        [
                                            'label' => 'PHP',
                                            'value' => 'php',
                                        ],
                                        [
                                            'label' => 'Laravel',
                                            'value' => 'laravel',
                                        ],
                                        [
                                            'label' => 'JavaScript',
                                            'value' => 'javascript',
                                        ],
                                        [
                                            'label' => 'React',
                                            'value' => 'react',
                                        ],
                                        [
                                            'label' => 'MySQL',
                                            'value' => 'mysql',
                                        ],
                                    ],

                                    'validation' => [],
                                ],

                            ],
                        ],

                        [
                            'id' => 'section_resume',
                            'title' => 'Resume',

                            'fields' => [

                                [
                                    'id' => 'field_resume',
                                    'key' => 'resume',
                                    'type' => 'file',
                                    'label' => 'Resume',
                                    'placeholder' => '',
                                    'help' =>
                                        'Upload your latest resume.',
                                    'default' => '',
                                    'required' => true,

                                    'validation' => [
                                        'max' => 10240,
                                        'file_types' => [
                                            'pdf',
                                            'doc',
                                            'docx',
                                        ],
                                    ],
                                ],

                            ],
                        ],

                    ],
                ],

                'settings' => [
                    'success_message' =>
                        'Thank you for your application.',
                ],

                'status' => 'published',

                'published_at' => now(),
            ]
        );
    }
}