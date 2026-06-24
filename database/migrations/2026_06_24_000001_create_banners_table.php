<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Banner Notifications — a super_admin-only, app-wide announcement bar shown at
 * the top of the app to a chosen audience (everyone / tutors / students).
 *
 * Used for cross-cutting notices the whole user base must see — e.g. "we are
 * moving to a new domain, reinstall the app from here", feature updates, etc.
 * Content is a SMALL allowlist of rich-text HTML (bold/italic/underline/
 * strikethrough/colour/link) sanitised server-side before it is stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();

            // Sanitised rich-text HTML (allowlist only — see HtmlSanitizer).
            $table->text('content');

            // Who sees it. Mirrors the Postgres CHECK below and config('wowlo.banner_audiences').
            $table->string('audience')->default('everyone');

            // Only active banners are rendered. Lets the owner keep a banner on
            // record and toggle it off without deleting it.
            $table->boolean('is_active')->default(true);

            // The super_admin who composed it (audit trail). Nulled if that
            // account is ever removed — the banner copy itself still stands.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // The hot query is "active banners for this audience", newest first.
            $table->index(['is_active', 'audience']);
        });

        // Mirror the canonical audience list as a DB-level guard (Postgres only;
        // the SQLite test DB skips it).
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE banners ADD CONSTRAINT banners_audience_check CHECK (audience IN ('everyone', 'tutors', 'students'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
