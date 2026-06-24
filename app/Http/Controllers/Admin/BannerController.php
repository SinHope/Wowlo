<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BannerRequest;
use App\Models\Banner;
use App\Support\HtmlSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Super_admin-only management of app-wide Banner Notifications. The route group
 * is behind role:super_admin, so no tutor/student ever reaches here.
 */
class BannerController extends Controller
{
    public function index(): View
    {
        $banners = Banner::with('creator')->latest()->paginate(15);

        return view('admin.banners.index', compact('banners'));
    }

    public function create(): View
    {
        return view('admin.banners.create');
    }

    public function store(BannerRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $content = HtmlSanitizer::clean($data['content']);

        if (trim(strip_tags($content)) === '') {
            return back()
                ->withInput()
                ->withErrors(['content' => 'The banner message looks empty after formatting. Please type some text.']);
        }

        Banner::create([
            'content' => $content,
            'audience' => $data['audience'],
            'is_active' => true,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.banners.index')
            ->with('status', 'Banner published.');
    }

    public function edit(Banner $banner): View
    {
        return view('admin.banners.edit', compact('banner'));
    }

    public function update(BannerRequest $request, Banner $banner): RedirectResponse
    {
        $data = $request->validated();

        $content = HtmlSanitizer::clean($data['content']);

        if (trim(strip_tags($content)) === '') {
            return back()
                ->withInput()
                ->withErrors(['content' => 'The banner message looks empty after formatting. Please type some text.']);
        }

        $banner->update([
            'content' => $content,
            'audience' => $data['audience'],
        ]);

        return redirect()->route('admin.banners.index')
            ->with('status', 'Banner updated.');
    }

    /** Flip a banner on/off without deleting it. */
    public function toggle(Banner $banner): RedirectResponse
    {
        $banner->update(['is_active' => ! $banner->is_active]);

        return back()->with('status', $banner->is_active ? 'Banner switched on.' : 'Banner switched off.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $banner->delete();

        return redirect()->route('admin.banners.index')
            ->with('status', 'Banner deleted.');
    }
}
