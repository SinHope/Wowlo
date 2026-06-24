<?php

namespace App\Http\Controllers;

use App\Models\PatchNote;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Public-facing Patch Notes — the changelog every authenticated user can read.
 * Creating/editing lives in Admin\PatchNoteController (super_admin only).
 */
class PatchNoteController extends Controller
{
    public function index(): View
    {
        $notes = PatchNote::with('creator')->latest()->paginate(15);

        return view('patch-notes.index', compact('notes'));
    }

    /**
     * Stream a note's attached image inline. Gated to any authenticated user
     * (patch notes are public to the whole user base) — never a public R2 URL.
     */
    public function image(PatchNote $patchNote): StreamedResponse
    {
        abort_unless($patchNote->hasImage(), 404);

        return Storage::disk('r2')->response($patchNote->image_path, $patchNote->image_name);
    }
}
