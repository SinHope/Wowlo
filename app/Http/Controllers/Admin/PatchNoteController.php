<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PatchNoteRequest;
use App\Models\PatchNote;
use App\Support\HtmlSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Super_admin-only authoring of Patch Notes (the route group is behind
 * role:super_admin). Reading them is public — see App\Http\Controllers\PatchNoteController.
 */
class PatchNoteController extends Controller
{
    public function create(): View
    {
        return view('admin.patch-notes.create');
    }

    public function store(PatchNoteRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $body = HtmlSanitizer::clean($data['body']);

        if (trim(strip_tags($body)) === '') {
            return back()->withInput()
                ->withErrors(['body' => 'The update text looks empty after formatting. Please write something.']);
        }

        $note = new PatchNote([
            'title' => $data['title'],
            'version' => $data['version'] ?? null,
            'body' => $body,
            'created_by' => $request->user()->id,
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $note->image_path = $image->store('patch-notes', 'r2');
            $note->image_name = $image->getClientOriginalName();
        }

        $note->save();

        return redirect()->route('patch-notes.index')->with('status', 'Patch note published.');
    }

    public function edit(PatchNote $patchNote): View
    {
        return view('admin.patch-notes.edit', ['note' => $patchNote]);
    }

    public function update(PatchNoteRequest $request, PatchNote $patchNote): RedirectResponse
    {
        $data = $request->validated();

        $body = HtmlSanitizer::clean($data['body']);

        if (trim(strip_tags($body)) === '') {
            return back()->withInput()
                ->withErrors(['body' => 'The update text looks empty after formatting. Please write something.']);
        }

        $patchNote->fill([
            'title' => $data['title'],
            'version' => $data['version'] ?? null,
            'body' => $body,
        ]);

        // Replace the image, or drop it if "remove" was ticked.
        if ($request->hasFile('image')) {
            $this->deleteImage($patchNote);
            $image = $request->file('image');
            $patchNote->image_path = $image->store('patch-notes', 'r2');
            $patchNote->image_name = $image->getClientOriginalName();
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($patchNote);
            $patchNote->image_path = null;
            $patchNote->image_name = null;
        }

        $patchNote->save();

        return redirect()->route('patch-notes.index')->with('status', 'Patch note updated.');
    }

    public function destroy(PatchNote $patchNote): RedirectResponse
    {
        $this->deleteImage($patchNote);
        $patchNote->delete();

        return redirect()->route('patch-notes.index')->with('status', 'Patch note deleted.');
    }

    /** Remove the note's image from R2 if it has one. */
    private function deleteImage(PatchNote $patchNote): void
    {
        if ($patchNote->hasImage()) {
            Storage::disk('r2')->delete($patchNote->image_path);
        }
    }
}
