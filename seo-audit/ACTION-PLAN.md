# Wowlo — SEO Action Plan

**Site:** https://wowlo.onrender.com/ · **Audit date:** 2026-06-08 · **Health score:** 66/100

Priorities: **Critical** = blocks indexing/penalty · **High** = clear ranking/sharing impact · **Medium** = optimisation · **Low** = polish.

> Scope reminder: only the public marketing pages (`/`, `/about`, `/how-to-use`, `/contact`, `/privacy-policy`) are SEO-relevant — the app is correctly behind auth. Aim to clear Critical + High **before** any public-signup launch or marketing push.

---

## Critical
*(none — nothing is currently blocking indexing or causing a penalty)*

---

## High (do before a public launch / marketing push)

> **✅ H1, H2, H3 implemented 2026-06-08** (dynamic `/sitemap.xml` route + `robots.txt` Sitemap line; `<x-seo-meta>` component adding canonical + Open Graph + Twitter tags to all 5 public pages; OG image defaults to the 512px app icon — **swap in a dedicated 1200×630 image** to finish H3). H4 still needs an ops check.

### H1 — Add `sitemap.xml` + reference it in `robots.txt`  ✅ DONE
- **Why:** Gives crawlers an explicit map of your 5 public URLs; speeds discovery and re-crawl.
- **Do:** Generate a static XML sitemap (or a Laravel route) listing the 5 public pages with `lastmod`. Add `Sitemap: https://wowlo.onrender.com/sitemap.xml` to `robots.txt`. Exclude all auth-gated routes.
- **Effort:** 30–45 min · **Files:** `routes/web.php` (or `public/sitemap.xml`), `public/robots.txt`.

### H2 — Add self-referencing canonical tags  ✅ DONE
- **Why:** Prevents duplicate-URL dilution (trailing slash, `?utm_*`, and a future custom domain vs the `onrender.com` host).
- **Do:** In the shared `<head>` (layout/partial), add `<link rel="canonical" href="{{ url()->current() }}">` (use the canonical host, not the request host, once you move to a custom domain).
- **Effort:** 20 min · **Files:** the public layout `<head>` partial.

### H3 — Add Open Graph + Twitter Card meta + a share image  ⚠️ MOSTLY DONE (needs 1200×630 image)
- **Why:** Highest-leverage fix for a **referral/word-of-mouth** Singapore tuition app — every link shared to WhatsApp/Telegram/Facebook/X currently renders bare. Also feeds AI/social preview surfaces.
- **Do:** Add per-page `og:title`, `og:description`, `og:type=website`, `og:url`, `og:image`, `og:site_name`, plus `twitter:card=summary_large_image`. Create one 1200×630 PNG/JPG share image (you can reuse brand assets / `seo-image-gen`).
- **Effort:** 45–60 min · **Files:** public layout `<head>`, `public/images/og/`.

### H4 — Mitigate Render cold starts
- **Why:** First request after idle can take 30–60s on the free tier — bad for crawl reliability and first impressions.
- **Do:** Confirm the **UptimeRobot keep-alive ping** (already in your deploy runbook) is active against production so the container stays warm. If organic/first-visit experience becomes important, budget for Render's paid always-on tier.
- **Effort:** 15 min to verify · **Files:** none (ops); see `docs/deployment-slice11-runbook.md`.

---

## Medium (within ~1 month)

> **✅ M1–M5 implemented 2026-06-08.** `WebApplication` + `WebSite` schema on the homepage; static FAQ section + `FAQPage` schema on `/how-to-use`; `llms.txt` added; `/privacy-policy` now has one `<h1>` (other languages → `<h2>`) and a meta description. All JSON-LD validated as parseable; 131 tests green.

### M1 — Add `WebApplication` (SoftwareApplication) schema  ✅ DONE
- **Why:** Wowlo *is* an app; this is the most relevant schema type and is currently missing. Eligible for richer results.
- **Do:** Add JSON-LD `@type: WebApplication`, `applicationCategory: EducationalApplication`, `operatingSystem: Web`, `offers` (even if free/`price: 0`), and link it to the existing Organization via `@id`.
- **Effort:** 30 min · **Files:** homepage schema partial.

### M2 — Add FAQ/HowTo content + schema to `/how-to-use`  ✅ DONE
- **Why:** Best single move for AI Overviews / rich results and for citable passages. You already have the walkthrough content.
- **Do:** Reformat key steps as Q→A, mark up with `FAQPage` (and/or `HowTo`) JSON-LD.
- **Effort:** 1–2 h · **Files:** `resources/views/how-to-use.blade.php`.

### M3 — Add `llms.txt`  ✅ DONE
- **Why:** Curated map for AI crawlers (what Wowlo is, key pages, Singapore focus).
- **Do:** Serve `/llms.txt` (markdown) summarising the product + linking the public pages.
- **Effort:** 30 min · **Files:** `public/llms.txt` or a route.

### M4 — Fix `/privacy-policy` meta description + headings  ✅ DONE
- **Why:** Missing description; 4× `<h1>` (one per official language) is a heading-structure nit.
- **Do:** Add a meta description; keep one `<h1>` (English) and demote the Malay/Chinese/Tamil headings to `<h2>`.
- **Effort:** 15 min · **Files:** `resources/views/privacy-policy.blade.php`.

### M5 — Add `WebSite` schema (basic, no SearchAction)  ✅ DONE
- **Effort:** 15 min · **Files:** layout schema partial.

---

## Low (backlog / polish)

- **L1 — Root `/favicon.ico` is 0 bytes.** Serve the real icon at the root path (browsers/bots probe it by default). *(10 min)*
- **L2 — Descriptive image alt text.** Replace the repeated `alt="Wowlo"` with specific descriptions on content images; keep logos terse. *(15 min)*
- **L3 — De-duplicate response headers** (`x-frame-options`, `x-content-type-options`, `referrer-policy` are emitted twice — app + nginx). *(15 min, ops)*
- **L4 — Brand the `/how-to-use` title** → "How to use Wowlo — Student & Tutor Guide". *(2 min)*
- **L5 — Logo as SVG, future screenshots as WebP** to trim bytes. *(as needed)*

---

## Suggested order (one focused session ≈ 3–4 h clears High + most Medium)

1. H1 sitemap + robots → 2. H2 canonical → 3. H3 OG/Twitter + image → 4. M4 privacy fixes → 5. M1 WebApplication schema → 6. M3 llms.txt → 7. M2 FAQ/HowTo → 8. verify H4 keep-alive → 9. Low items.

## Measurement
- **Connect Google Search Console** (verify the domain, submit the new sitemap) to unlock real indexation status + CWV field data — the one thing this audit couldn't measure. Re-run the audit after the High items land to confirm the score moves into the 80s.
