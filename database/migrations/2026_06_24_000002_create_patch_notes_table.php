<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Patch Notes (Slice 16) — a public changelog of updates.
 *
 * Readable by EVERY authenticated user (tutors + students + the super_admin);
 * only the super_admin can create/edit/delete (route group is role:super_admin).
 * `body` is sanitised allowlist HTML (see App\Support\HtmlSanitizer). An optional
 * image lives on the private R2 bucket — the DB stores only the object key and
 * the image streams through an authorized controller route (never a public URL).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patch_notes', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            // Free-text version label, e.g. "v2.3" or "June 2026 update".
            $table->string('version')->nullable();

            // Sanitised rich-text HTML (bold/italic/underline/strikethrough/bullets).
            $table->text('body');

            // R2 object key for the optional attached image (+ its original name
            // for the inline stream's filename). Null when there's no image.
            $table->string('image_path')->nullable();
            $table->string('image_name')->nullable();

            // The composing super_admin (audit trail); nulled if removed.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patch_notes');
    }
};
