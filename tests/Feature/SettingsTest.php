<?php

use App\Support\AppBackgrounds;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

// A throwaway category + image under public/app-background, removed after each test.
beforeEach(function () {
    $this->dir = public_path('app-background/ZZ Test Cat');
    File::ensureDirectoryExists($this->dir);
    File::put($this->dir . '/pic one.jpg', 'x');
});
afterEach(fn () => File::deleteDirectory($this->dir));

it('requires login', function () {
    get(route('settings.edit'))->assertRedirect(route('login'));
});

it('defaults a user to the system theme with no background', function () {
    $u = tutor();
    expect($u->fresh()->theme)->toBe('system')->and($u->fresh()->app_background)->toBeNull();
    actingAs($u)->get(route('settings.edit'))->assertOk()->assertSee('Appearance')->assertSee('ZZ Test Cat');
});

it('saves theme and a valid background', function () {
    $u = student();
    actingAs($u)->patch(route('settings.update'), ['theme' => 'dark', 'app_background' => 'ZZ Test Cat/pic one.jpg'])
        ->assertSessionHasNoErrors();

    expect($u->fresh()->theme)->toBe('dark')->and($u->fresh()->app_background)->toBe('ZZ Test Cat/pic one.jpg');

    // The layout reflects it on the next page load.
    actingAs($u->fresh())->get(route('settings.edit'))
        ->assertSee('data-theme="dark"', false)
        ->assertSee('--app-bg: url(', false)
        ->assertSee('ZZ%20Test%20Cat/pic%20one.jpg', false);
});

it('clears the background with an empty value', function () {
    $u = tutor(['app_background' => 'ZZ Test Cat/pic one.jpg']);
    actingAs($u)->patch(route('settings.update'), ['theme' => 'light', 'app_background' => ''])->assertSessionHasNoErrors();
    expect($u->fresh()->app_background)->toBeNull();
});

it('rejects an unknown theme', function () {
    actingAs(tutor())->patch(route('settings.update'), ['theme' => 'neon'])->assertSessionHasErrors('theme');
});

it('rejects traversal, missing files, wrong types and deeper paths as a background', function (string $bad) {
    $u = tutor();
    actingAs($u)->patch(route('settings.update'), ['theme' => 'light', 'app_background' => $bad])
        ->assertSessionHasErrors('app_background');
    expect($u->fresh()->app_background)->toBeNull();
})->with([
    '../../.env',
    'ZZ Test Cat/../../index.php',
    'ZZ Test Cat/missing.jpg',
    'ZZ Test Cat/.gitkeep',
    'ZZ Test Cat/sub/pic one.jpg',
    '/etc/passwd',
]);

it('only ever changes the acting user (no id accepted)', function () {
    $a = tutor();
    $b = tutor();
    actingAs($a)->patch(route('settings.update'), ['theme' => 'dark', 'id' => $b->id, 'user_id' => $b->id]);
    expect($a->fresh()->theme)->toBe('dark')->and($b->fresh()->theme)->toBe('system');
});

it('defaults card opacity to solid and saves a slider value', function () {
    $u = tutor(['app_background' => 'ZZ Test Cat/pic one.jpg']);
    expect($u->fresh()->card_opacity)->toBe(100);

    // Solid: no glass attribute on the page.
    actingAs($u)->get(route('settings.edit'))->assertSee('--surface-alpha: 1', false)->assertDontSee('data-glass="1"', false);

    actingAs($u)->patch(route('settings.update'), ['theme' => 'light', 'app_background' => 'ZZ Test Cat/pic one.jpg', 'card_opacity' => 70])
        ->assertSessionHasNoErrors();
    expect($u->fresh()->card_opacity)->toBe(70);

    actingAs($u->fresh())->get(route('settings.edit'))
        ->assertSee('--surface-alpha: 0.7', false)
        ->assertSee('data-glass="1"', false);
});

it('rejects card opacity outside the allowed range', function (mixed $bad) {
    $u = tutor();
    actingAs($u)->patch(route('settings.update'), ['theme' => 'light', 'card_opacity' => $bad])
        ->assertSessionHasErrors('card_opacity');
    expect($u->fresh()->card_opacity)->toBe(100);
})->with([10, 49, 101, 'abc']);

it('ignores a stored background whose file was removed', function () {
    expect(AppBackgrounds::urlFor('ZZ Test Cat/gone.jpg'))->toBeNull();
    $u = tutor(['app_background' => 'ZZ Test Cat/gone.jpg']);
    actingAs($u)->get(route('settings.edit'))->assertOk()->assertDontSee('--app-bg: url(', false);
});
