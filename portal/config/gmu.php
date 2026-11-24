<?php

return [

    /*
    |--------------------------------------------------------------------------
    | External grants modules
    |--------------------------------------------------------------------------
    |
    | Funding calls, proposals, awards, reports, documents, donors, the library,
    | researcher profiles, analytics and the AI hub. NIMR has not taken these
    | up, so they are hidden by default: their menu items disappear and their
    | routes answer 404. Nothing is deleted; set GMU_EXTERNAL_GRANTS=true to
    | bring them back.
    |
    */

    'external_grants' => (bool) env('GMU_EXTERNAL_GRANTS', false),

    /*
    |--------------------------------------------------------------------------
    | Staff profile lists (first sign-in form)
    |--------------------------------------------------------------------------
    |
    | GMU should confirm both lists against the establishment.
    |
    */

    // Where people write to GMU, shown on the "Something went wrong" page.
    'contact_email' => env('GMU_CONTACT_EMAIL', 'grantsunit@nimr.or.tz'),

    'designations' => [
        'Research Scientist II',
        'Research Scientist I',
        'Senior Research Scientist',
        'Principal Research Scientist',
        'Chief Research Scientist',
        'Laboratory Scientist',
        'Health Laboratory Officer',
        'Other',
    ],

    'centres' => [
        'Headquarters',
        'Amani Centre',
        'Dodoma Centre',
        'Mbeya Medical Research Centre',
        'Muhimbili Centre',
        'Mwanza Centre',
        'Ngongongare Centre',
        'Tabora Centre',
        'Tanga Centre',
        'Tukuyu Centre',
    ],

];
