<?php

use App\Models\PatchNote;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Patch Notes (Slice 16) — a public changelog. Everyone authenticated can read;
 * only the super_admin can create/edit/delete. Proves that authorization split,
 * body sanitisation (incl. bullet lists), and R2 image handling.
 * tutor()/superAdmin()/student() live in Pest.php.
 */

// ---- Reading (everyone) -----------------------------------------------------

it('lets every authenticated role read patch notes', function () {
    PatchNote::create(['title' => 'Hello', 'body' => '<p>Hi</p>']);

    $this->actingAs(superAdmin())->get(route('patch-notes.index'))->assertOk()->assertSee('Hello');
    $this->actingAs(tutor())->get(route('patch-notes.index'))->assertOk()->assertSee('Hello');
    $this->actingAs(student(['tutor_id' => tutor()->id]))->get(route('patch-notes.index'))->assertOk()->assertSee('Hello');
});

it('requires login to read patch notes', function () {
    $this->get(route('patch-notes.index'))->assertRedirect(route('login'));
});

it('shows the New Note button only to the super_admin', function () {
    $this->actingAs(superAdmin())->get(route('patch-notes.index'))->assertSee('New Note');
    $this->actingAs(tutor())->get(route('patch-notes.index'))->assertDontSee('New Note');
});

// ---- Authoring is super_admin only ------------------------------------------

it('blocks tutors and students from authoring', function () {
    $this->actingAs(tutor())->get(route('admin.patch-notes.create'))->assertForbidden();
    $this->actingAs(student(['tutor_id' => tutor()->id]))->get(route('admin.patch-notes.create'))->assertForbidden();

    $this->actingAs(tutor())
        ->post(route('admin.patch-notes.store'), ['title' => 'X', 'body' => '<b>x</b>'])
        ->assertForbidden();

    expect(PatchNote::count())->toBe(0);
});

// ---- Compose + sanitise -----------------------------------------------------

it('stores a note and strips disallowed HTML but keeps bullet lists', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.patch-notes.store'), [
            'title' => 'June update',
            'version' => 'v2.3',
            'body' => '<p>Fixed <b>bugs</b><script>alert(1)</script></p><ul><li>One</li><li>Two</li></ul>',
        ])
        ->assertRedirect(route('patch-notes.index'));

    $note = PatchNote::firstOrFail();

    expect($note->title)->toBe('June update')
        ->and($note->version)->toBe('v2.3')
        ->and($note->body)->toContain('<b>bugs</b>')
        ->and($note->body)->toContain('<li>One</li>')
        ->and($note->body)->not->toContain('<script')
        ->and($note->created_by)->not->toBeNull();
});

it('requires a title and body', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.patch-notes.store'), ['title' => '', 'body' => ''])
        ->assertSessionHasErrors(['title', 'body']);
});

it('rejects a body that is empty after sanitising', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.patch-notes.store'), ['title' => 'X', 'body' => '<script>bad()</script>'])
        ->assertSessionHasErrors('body');

    expect(PatchNote::count())->toBe(0);
});

// ---- Image on R2 ------------------------------------------------------------

it('stores an attached image on R2 and streams it inline', function () {
    Storage::fake('r2');

    $this->actingAs(superAdmin())->post(route('admin.patch-notes.store'), [
        'title' => 'With image',
        'body' => '<p>see below</p>',
        'image' => UploadedFile::fake()->image('shot.png', 600, 400),
    ])->assertRedirect();

    $note = PatchNote::firstOrFail();

    expect($note->image_path)->not->toBeNull();
    Storage::disk('r2')->assertExists($note->image_path);

    // Any authenticated user can view the image.
    $this->actingAs(student(['tutor_id' => tutor()->id]))
        ->get(route('patch-notes.image', $note))
        ->assertOk();
});

it('404s the image route when the note has no image', function () {
    $note = PatchNote::create(['title' => 'No image', 'body' => '<p>x</p>']);

    $this->actingAs(tutor())->get(route('patch-notes.image', $note))->assertNotFound();
});

it('removes the image from R2 when asked on update', function () {
    Storage::fake('r2');

    $this->actingAs(superAdmin())->post(route('admin.patch-notes.store'), [
        'title' => 'Img',
        'body' => '<p>x</p>',
        'image' => UploadedFile::fake()->image('a.png'),
    ]);

    $note = PatchNote::firstOrFail();
    $oldPath = $note->image_path;

    $this->actingAs(superAdmin())->put(route('admin.patch-notes.update', $note), [
        'title' => 'Img',
        'body' => '<p>x</p>',
        'remove_image' => '1',
    ])->assertRedirect();

    expect($note->fresh()->image_path)->toBeNull();
    Storage::disk('r2')->assertMissing($oldPath);
});

// ---- Delete -----------------------------------------------------------------

it('deletes a note and its image', function () {
    Storage::fake('r2');

    $this->actingAs(superAdmin())->post(route('admin.patch-notes.store'), [
        'title' => 'Bye',
        'body' => '<p>x</p>',
        'image' => UploadedFile::fake()->image('b.png'),
    ]);

    $note = PatchNote::firstOrFail();
    $path = $note->image_path;

    $this->actingAs(superAdmin())
        ->delete(route('admin.patch-notes.destroy', $note))
        ->assertRedirect(route('patch-notes.index'));

    expect(PatchNote::find($note->id))->toBeNull();
    Storage::disk('r2')->assertMissing($path);
});
