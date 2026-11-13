<?php

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    config(['app.debug' => false]);
});

it('renders the friendly 404 page for an unknown URL', function () {
    $this->get('/definitely-not-a-real-page')
        ->assertNotFound()
        ->assertSee('Page not found')
        ->assertSee('Go to Dashboard')
        ->assertSee('noindex', false);
});

it('renders a custom page for each handled status code', function (int $status, string $heading) {
    Route::middleware('web')->get('/_test/abort/'.$status, fn () => abort($status));

    $this->get('/_test/abort/'.$status)
        ->assertStatus($status)
        ->assertSee($heading)
        ->assertSee('Contact us');
})->with([
    [403, "You don't have access"],
    [419, 'Page expired'],
    [429, 'Too many requests'],
    [500, 'Something went wrong'],
    [503, 'Back soon'],
]);

it('uses the generic fallback pages for other 4xx and 5xx codes', function () {
    Route::middleware('web')->get('/_test/abort/418', fn () => abort(418));
    Route::middleware('web')->get('/_test/abort/502', fn () => abort(502));

    $this->get('/_test/abort/418')->assertStatus(418)->assertSee('Request problem');
    $this->get('/_test/abort/502')->assertStatus(502)->assertSee('Something went wrong');
});

it('never leaks exception details on an unexpected error', function () {
    Route::middleware('web')->get('/_test/boom', fn () => throw new RuntimeException('secret-database-password-leak'));

    $this->get('/_test/boom')
        ->assertStatus(500)
        ->assertSee('Something went wrong')
        ->assertDontSee('secret-database-password-leak')
        ->assertDontSee('RuntimeException');
});

it('renders the 500 page for a guest (no auth or session needed)', function () {
    Route::middleware('web')->get('/_test/guest-500', fn () => abort(500));

    $this->assertGuest();
    $this->get('/_test/guest-500')->assertStatus(500)->assertSee('Something went wrong');
});
