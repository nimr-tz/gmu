<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The owner's "Error" design: each error says what happened and where to go next. */
class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_missing_page_says_so_and_offers_the_dashboard(): void
    {
        $this->get('/no-such-page')->assertNotFound()
            ->assertSee('Page not found')->assertSee('Go to your dashboard');
    }

    public function test_each_error_page_renders_its_own_words(): void
    {
        foreach ([
            '403' => 'You don’t have access to this page',
            '419' => 'This page has expired',
            '500' => 'Something went wrong',
        ] as $code => $title) {
            $html = view('errors.' . $code)->render();
            $this->assertStringContainsString($title, $html);
            $this->assertStringContainsString('ERROR ' . $code, $html);
        }

        $this->assertStringContainsString(config('gmu.contact_email'), view('errors.500')->render());
        $this->assertStringContainsString('Try again', view('errors.419')->render());
    }
}
