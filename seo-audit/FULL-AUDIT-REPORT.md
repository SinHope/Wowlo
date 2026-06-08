# Wowlo — Full SEO Audit Report

**Site audited:** https://wowlo.onrender.com/
**Date:** 2026-06-08
**Pages analysed:** 5 public pages (`/`, `/about`, `/how-to-use`, `/contact`, `/privacy-policy`)
**Method:** Live HTTP crawl + HTML/header analysis (no GSC/CrUX field data available)

---

## Executive Summary

### Overall SEO Health Score: **66 / 100** — *Good foundation, fixable gaps*

| Category | Score | Weight | Contribution |
|---|---|---|---|
| Technical SEO | 62 | 22% | 13.6 |
| Content Quality | 70 | 23% | 16.1 |
| On-Page SEO | 68 | 20% | 13.6 |
| Schema / Structured Data | 60 | 10% | 6.0 |
| Performance (CWV) | 75 | 10% | 7.5 |
| AI Search Readiness | 50 | 10% | 5.0 |
| Images | 75 | 5% | 3.8 |
| **Total** | | | **≈ 66** |

**Business type detected:** SaaS / web-app product site — a **tuition-management app for Singapore** (tutors, students, parents). Local-entity signal is strong (`areaServed: Singapore`).

> **Strategic context (read first):** Wowlo is mostly a *gated application* — homework, fees, messages, quizzes all sit behind auth and are correctly **not** indexable. Your entire SEO surface is the **public marketing layer**: the landing page, `/about`, `/how-to-use`, and `/contact`. Accounts are currently admin-created (no public sign-up yet), so organic search is not a growth channel *today* — but it becomes one the moment Phase 2 (public tutor sign-up) ships. **Treat this audit as getting the marketing surface launch-ready, not as an urgent traffic problem.** The fixes below are cheap and durable; do the Critical/High ones before any public-signup launch or marketing push.

### Top 5 Critical / High issues
1. **No `sitemap.xml`** — returns 404. Search engines have no crawl map. *(High)*
2. **No canonical tags** on any page — duplicate-URL ambiguity risk (e.g. trailing slash, query params, future custom domain). *(High)*
3. **No Open Graph / Twitter Card tags** — links shared to WhatsApp/Facebook/X/LinkedIn render with no title, description, or preview image. For a referral-driven Singapore tuition app, this is the highest-leverage miss. *(High)*
4. **Render free-tier cold starts** — the service scales to zero; the first request after idle can take 30–60s. Crawlers and first-time visitors may hit a timeout/slow load even though warm performance is excellent. *(High)*
5. **`/privacy-policy` has no meta description and four `<h1>`s** (one per official language). *(Medium)*

### Top 5 Quick Wins (each < 1 hour)
1. Add a `sitemap.xml` (5 public URLs) + reference it in `robots.txt`.
2. Add a self-referencing `<link rel="canonical">` to the shared layout.
3. Add Open Graph + Twitter Card meta + one 1200×630 share image.
4. Add a meta description to `/privacy-policy`.
5. Add `llms.txt` for AI-crawler discoverability (you already have clean Organization schema to build on).

---

## 1. Technical SEO — 62/100

**Strengths**
- ✅ HTTPS enforced, **HSTS** present (`max-age=31536000; includeSubDomains`).
- ✅ Strong security headers: `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` locking geolocation/mic/camera.
- ✅ `robots.txt` present and permissive (`Disallow:` = allow all).
- ✅ Server-rendered HTML (Blade) — no JS-rendering dependency for crawlers.
- ✅ `lang="en"`, responsive `viewport`, PWA `manifest.json`, `theme-color`.
- ✅ HTTP/3 advertised (`alt-svc: h3`), Cloudflare in front, served from **SIN** edge.

**Issues**
- ❌ **`sitemap.xml` → 404.** No XML sitemap exists. *(High)*
- ❌ **No `Sitemap:` directive** in `robots.txt`. *(Medium — fix with the above)*
- ❌ **No canonical tags** anywhere. *(High)*
- ⚠️ **`/favicon.ico` at root returns HTTP 200 but 0 bytes.** The linked `/images/favicon/wowlo_favicon.ico` is valid (684 B), but bots/browsers that probe the root path get an empty file. *(Low)*
- ⚠️ **Duplicate headers**: `referrer-policy`, `x-content-type-options`, `x-frame-options` are each emitted twice (likely set in both app middleware and the Render/nginx layer). Harmless but untidy. *(Low)*

---

## 2. Content Quality — 70/100

- ✅ Clear, benefit-led value proposition; homepage ≈ 950 words — adequate depth for a product landing page.
- ✅ `/about` carries a Singapore origin story (a genuine E-E-A-T / "experience" signal) and `/how-to-use` gives real product guidance.
- ✅ Distinct, non-duplicated content across the five pages.
- ⚠️ E-E-A-T is thin on *authorship/trust* beyond the Organization block — no named founder/team, no contact entity detail surfaced in markup. Acceptable for a small product site; worth a light touch later.
- ⚠️ No blog / resource content. Fine today; it's the obvious lever **if** organic acquisition becomes a goal at Phase 2 (e.g. "PSLE past papers", "tuition fee tracker Singapore").

---

## 3. On-Page SEO — 68/100

| Page | Title | Meta Description | H1 |
|---|---|---|---|
| `/` | ✅ "Wowlo — Tuition, organised." | ✅ present (~110 chars) | ✅ 1 |
| `/about` | ✅ "About Wowlo — Tuition, all in one place." | ✅ present | ✅ 1 |
| `/how-to-use` | ✅ "How to use Wowlo" | ✅ present | ✅ 1 |
| `/contact` | ✅ "Contact Us — Wowlo" | ✅ present | ✅ 1 |
| `/privacy-policy` | ✅ "Privacy Policy — Wowlo" | ❌ **missing** | ⚠️ **4** |

- ✅ Titles are unique, descriptive, and brand-suffixed.
- ✅ Heading hierarchy on the homepage is healthy (1× H1, 5× H2, 9× H3).
- ❌ **No Open Graph / Twitter Card tags** on any page (0 found). *(High)*
- ❌ `/privacy-policy` missing meta description. *(Medium)*
- ⚠️ `/privacy-policy` has 4× `<h1>` — these are the same heading in English, Malay, Chinese, and Tamil (Singapore's four official languages). Keep one `<h1>` and demote the language variants to `<h2>`. *(Low)*
- ⚠️ `/how-to-use` title lacks the "Wowlo" brand token — consider "How to use Wowlo — Student & Tutor Guide". *(Low)*

---

## 4. Schema / Structured Data — 60/100

- ✅ Valid **Organization** JSON-LD on the homepage: `name`, `url`, `logo`, `description`, `@id`, and `areaServed: Singapore`. Clean and well-formed.
- ❌ Missing **WebSite** schema (with `potentialAction`/SearchAction if you add site search).
- ❌ Missing **WebApplication / SoftwareApplication** schema — Wowlo *is* an app; this is the single most relevant type and is absent. Add `applicationCategory: EducationalApplication`, `operatingSystem: Web`, and an `offers` block.
- ❌ Missing **BreadcrumbList** on inner pages.
- ❌ `/how-to-use` is a natural fit for **HowTo** and/or **FAQPage** schema — strong AI-Overview/rich-result opportunity.
- ⚠️ Organization schema lives only on the homepage; an `@id`-referenced Organization (or `sameAs` social links) sitewide would strengthen the entity.

---

## 5. Performance (Core Web Vitals) — 75/100

**Warm (edge-cached connection):**
- TTFB **0.157s**, total **0.157s**, HTML **38.4 KB**, DNS 0.011s, connect 0.020s — **excellent**.

**Risks**
- ⚠️ **Cold start (the dominant real-world factor):** Render free tier *scales to zero*. The first request after idle spins the container up — typically **30–60s**. Googlebot and first-time visitors can hit this. This will not show in warm lab numbers but is the biggest practical performance and crawl-reliability issue.
- ℹ️ No **field data** (CrUX/GSC) available — connect Google Search Console to get real LCP/INP/CLS from users.
- ℹ️ A keep-alive ping already exists in your stack (UptimeRobot per the deploy runbook) — confirm it's active in production, as it directly mitigates the cold-start problem.

---

## 6. Images — 75/100

- ✅ All 4 homepage images carry `alt` text.
- ⚠️ Alt text is generic/duplicated ("Wowlo" on every image). Make it descriptive where the image is content (e.g. "Wowlo dashboard showing homework and fees"); keep it terse for pure logos.
- ⚠️ Logos are PNG — fine at this size; consider SVG for the logo and WebP for any future screenshots to cut bytes and avoid CLS.
- ✅ No oversized or layout-shifting images detected on the pages crawled.

---

## 7. AI Search Readiness (GEO) — 50/100

- ❌ **No `llms.txt`** (404) — add one so AI crawlers get a concise, curated map of what Wowlo is and which pages to cite.
- ❌ **No Open Graph** — many AI/social surfaces use OG for titles/snippets/preview cards.
- ✅ Organization schema + explicit `areaServed: Singapore` is a solid entity foundation for "tuition app Singapore"-type AI answers.
- ⚠️ Content is light on **citable, self-contained passages** (clear Q→A statements). Adding a FAQ to `/how-to-use` (with FAQPage schema) is the highest-value GEO move.

---

## Crawl / Access Notes

- `robots.txt`: `User-agent: * / Disallow:` — nothing blocked. Good.
- No rate-limiting or 429s encountered.
- All 5 public pages returned **HTTP 200**.
- App routes (dashboard, tutor/*, student/*) are auth-gated and correctly excluded from the public surface — no action needed (do **not** expose them to indexing).

See **ACTION-PLAN.md** for the prioritised, effort-estimated fix list.
