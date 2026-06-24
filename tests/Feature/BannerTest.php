<?php

use App\Models\Banner;

/**
 * Banner Notifications — a super_admin-only, app-wide announcement bar.
 *
 * Proves the management area is locked to the super_admin, that audience
 * targeting controls who sees a banner, and that composed HTML is sanitised
 * down to the allowlist before it is ever rendered to users.
 * tutor()/superAdmin()/student() live in Pest.php.
 */

// ---- Authorization ----------------------------------------------------------

it('lets the super_admin manage banners', function () {
    $this->actingAs(superAdmin())
        ->get(route('admin.banners.index'))
        ->assertOk();
});

it('blocks tutors and students from the banner area', function () {
    $this->actingAs(tutor())->get(route('admin.banners.index'))->assertForbidden();
    $this->actingAs(tutor())->get(route('admin.banners.create'))->assertForbidden();

    $student = student(['tutor_id' => tutor()->id]);
    $this->actingAs($student)->get(route('admin.banners.index'))->assertForbidden();
});

it('forbids a tutor from creating a banner', function () {
    $this->actingAs(tutor())
        ->post(route('admin.banners.store'), ['content' => '<b>Hi</b>', 'audience' => 'everyone'])
        ->assertForbidden();

    expect(Banner::count())->toBe(0);
});

// ---- Compose + sanitise -----------------------------------------------------

it('stores a banner and strips disallowed HTML', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.banners.store'), [
            'content' => '<p>Hello <b>world</b><script>alert(1)</script> <a href="javascript:alert(2)">x</a></p>',
            'audience' => 'everyone',
        ])
        ->assertRedirect(route('admin.banners.index'));

    $banner = Banner::firstOrFail();

    expect($banner->content)->toContain('<b>world</b>')
        ->and($banner->content)->not->toContain('<script')
        ->and($banner->content)->not->toContain('alert(1)')
        ->and($banner->content)->not->toContain('javascript:')
        ->and($banner->is_active)->toBeTrue()
        ->and($banner->created_by)->not->toBeNull();
});

it('keeps a safe link and forces it to open safely', function () {
    $this->actingAs(superAdmin())->post(route('admin.banners.store'), [
        'content' => '<a href="https://wowlo.app/install">Reinstall</a>',
        'audience' => 'everyone',
    ]);

    $content = Banner::firstOrFail()->content;

    expect($content)->toContain('href="https://wowlo.app/install"')
        ->and($content)->toContain('target="_blank"')
        ->and($content)->toContain('rel="noopener noreferrer"');
});

it('rejects content that is empty after sanitising', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.banners.store'), ['content' => '<script>bad()</script>', 'audience' => 'everyone'])
        ->assertSessionHasErrors('content');

    expect(Banner::count())->toBe(0);
});

it('rejects an unknown audience', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.banners.store'), ['content' => '<b>Hi</b>', 'audience' => 'aliens'])
        ->assertSessionHasErrors('audience');
});

// ---- Audience targeting (visibleTo scope) -----------------------------------

it('shows an everyone banner to both tutors and students', function () {
    Banner::create(['content' => '<b>For all</b>', 'audience' => 'everyone', 'is_active' => true]);

    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]);

    expect(Banner::visibleTo($tutor)->count())->toBe(1)
        ->and(Banner::visibleTo($student)->count())->toBe(1);
});

it('hides a students-only banner from tutors', function () {
    Banner::create(['content' => 'students', 'audience' => 'students', 'is_active' => true]);

    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]);

    expect(Banner::visibleTo($tutor)->count())->toBe(0)
        ->and(Banner::visibleTo($student)->count())->toBe(1);
});

it('hides a tutors-only banner from students and shows it to the super_admin', function () {
    Banner::create(['content' => 'tutors', 'audience' => 'tutors', 'is_active' => true]);

    $student = student(['tutor_id' => tutor()->id]);

    expect(Banner::visibleTo($student)->count())->toBe(0)
        ->and(Banner::visibleTo(superAdmin())->count())->toBe(1);
});

it('never shows an inactive banner', function () {
    Banner::create(['content' => 'off', 'audience' => 'everyone', 'is_active' => false]);

    expect(Banner::visibleTo(tutor())->count())->toBe(0);
});

// ---- Toggle + delete --------------------------------------------------------

it('toggles a banner on and off', function () {
    $banner = Banner::create(['content' => 'x', 'audience' => 'everyone', 'is_active' => true]);

    $this->actingAs(superAdmin())->patch(route('admin.banners.toggle', $banner))->assertRedirect();
    expect($banner->fresh()->is_active)->toBeFalse();

    $this->actingAs(superAdmin())->patch(route('admin.banners.toggle', $banner))->assertRedirect();
    expect($banner->fresh()->is_active)->toBeTrue();
});

it('deletes a banner', function () {
    $banner = Banner::create(['content' => 'x', 'audience' => 'everyone', 'is_active' => true]);

    $this->actingAs(superAdmin())
        ->delete(route('admin.banners.destroy', $banner))
        ->assertRedirect(route('admin.banners.index'));

    expect(Banner::find($banner->id))->toBeNull();
});
