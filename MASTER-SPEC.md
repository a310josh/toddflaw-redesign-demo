# TMF Site Rebuild: Master Orchestration Spec

Owner: Josh (josh@get310.com). Orchestrator: Claude (Cowork). Executors: Hermes (via #hermes-channel), Claude Code.
Design reference (approved): https://toddflaw-redesign-demo.vercel.app · Repo: https://github.com/a310josh/toddflaw-redesign-demo

## 1. Architecture (decided, do not relitigate)

- **Next.js (latest stable) App Router on Vercel**, JavaScript, no Tailwind. Global CSS = `assets/tmf.css` from the reference repo (port verbatim as `app/globals` layer; it is the design system: tokens, components, mobile rules).
- **URLs mirror WordPress exactly**: `trailingSlash: true`, folder routes (`/prussia/`, never `/prussia.html`). Preserving every URL in `inventory.csv` 1:1 is a hard requirement; anything intentionally dropped gets a 301 in `next.config` redirects.
- **Blog = headless WordPress.** Posts/categories fetched from `https://toddflaw.com/wp-json/wp/v2/` with ISR (`revalidate: 3600`). Blog archive at `/blog/`, posts at their existing permalinks.
- **Page content source**: WP REST `pages` endpoint by ID (e.g. Home = 56725 → `/wp-json/wp/v2/pages/56725`). Strip WP markup to clean semantic content; re-house it in the approved templates. Copy is preserved/edited lightly for answer-first structure, never invented facts.
- **Media**: download all referenced images/video to `/public` (or WP stays the media host short-term; flag hotlinks in PR notes). `next/image` everywhere; `next/font` self-hosts Archivo + Inter.
- **New repo**: `toddflaw-next` (Hermes creates, personal GitHub `a310josh`, Vercel import same as demo project).

## 2. Templates (port from prototype HTML in this repo)

| # | Template | Prototype source | Used by |
|---|---|---|---|
| T1 | Home (hero video rotator with ORIGINAL b-roll `2021/03/bdv_tflaw_herovid_v1.mp4`, logo wall, zig practice, **video feature section playing `2025/03/Consumer-Rights-Attorney-Todd-M.-Friedman-1.mp4` with VideoObject schema**, dockets, review rail, office explorer) | `index.html` | `/` |
| T2 | Interior/Practice (hero image, stat band, TOC scroll-spy, sub-cards, stepper, verdict stamps, spotlight, office tabs, FAQ accordion) | `employment-law.html` | all practice + sub-practice pages |
| T3 | Attorney profile. **Graceful degradation (Josh 7/26):** Awards & Badges card renders only when badge images exist; Super Lawyers timeline only when selections exist; thin-highlight attorneys lead the sidebar with Practice Focus, bar admissions, education. Same layout and vibe regardless of highlight count; never pad with invented recognitions. | `attorney-todd-michael-friedman.html` | `/about/…` attorney pages (team-sitemap) |
| T4 | Blog archive (filter chips = WP categories, search, pagination) | `blog.html` | `/blog/` |
| T5 | Blog post (new: T2 typography + matched verdict stamp + share + CTA) | derive | ~800 posts via ISR |
| T6 | About/team grid | `about.html` | `/about/` |
| T7 | Contact (form-card + office explorer + NAP block, LocalBusiness schema) | derive from CTA band | `/contact/`, `/free-consultation/` |
| T8 | Results archive (docket grid + practice-area filter, amounts sorted) | derive from home dockets | `/results/` (+ result-sitemap entries) |
| T9 | Testimonials (spotlight + review rail, AggregateRating where legitimate) | derive | `/what-our-clients-say/` |
| T10 | Video Center (grid of players, VideoObject schema each) | derive from blog cards | `/video-center/` |
| T11 | Location hub (city hero image, local offices, local practice links, LegalService+areaServed) | T2 variant | `/los-angeles/`, `/chicago/`, `/cleveland/`, `/prussia/` |

Validation content (case results, testimonials) lives as JSON collections in `/content/results.json` and `/content/testimonials.json`, each item tagged `practiceArea`; templates pull matching items (fallback: latest). Seed from `/results/` page + result-sitemap URLs + what-our-clients-say.

## 3. AEO / GEO foundation (every page, non-negotiable)

- JSON-LD graph: `LegalService` (org, NAP for all 4 offices, sameAs socials) site-wide; per page add `BreadcrumbList`; `FAQPage` wherever FAQ accordions exist; `Attorney`/`Person` on T3; `Review` on T9 (real reviews only); `VideoObject` on T10 + home hero video; `BlogPosting` on T5.
- **Answer-first content blocks**: each interior page leads its sections with question-styled H2s followed by a 40–60 word direct answer, then depth. This is the AEO restructure applied during content port.
- `llms.txt` at root (site map for LLM crawlers: firm facts, practice areas, offices, key results) + per-template clean semantic HTML (single H1, hierarchical H2/H3, no div-soup).
- `speakable` schema on FAQ answers. Entity consistency: identical firm name/address/phone strings everywhere.
- OpenGraph + Twitter cards per page; canonical; `sitemap.xml` + `robots.txt` generated at build.

## 4. SEO + performance

- Metadata API per route (title ≤60ch, description ≤155ch — port/improve from Rank Math values in page HTML `<head>`).
- CWV budget: mobile LCP < 2.5s, CLS < 0.05. Hero video `preload="none"` + poster; images lazy + sized.
- Internal linking: every child links up to parent hub + siblings ("Related in Employment Law" block).

## 5. UX / CTA / sharing

- Sticky mobile call bar (call + free evaluation) appearing after 600px scroll on all pages.
- Share: native `navigator.share` button on posts/videos with clipboard fallback; no third-party share scripts.
- Free case evaluation form on every template (posts to a `/api/lead` stub; wire Gravity Forms/CRM later; include honeypot). Required fields per Josh 7/26: Name, Phone, Email, State, **Type of Case (Employment Law / Consumer Protection / Personal Injury / Lemon Law / Business Litigation / Other)**, Contact Preference (placeholder "Select...", never long text in half-width selects), Case Description.
- Mobile-first: build at 390px first; the tmf.css v2 media rules are the reference. **Definition of done per page includes a 390px screenshot in the PR.**

## 6. Tracking

- `app/layout` includes `<Analytics/>` slot component reading `NEXT_PUBLIC_GTM_ID` env (GTM container injected in head when set; noop otherwise). One insertion point, nothing else touches head.

## 7. Batches

- **Batch 1 (now)**: `/` (56725), `/about/` (56948), `/blog/` (56726), `/employment-law/` (60461), `/consumer-rights/` (58456), `/contact/` (56971), `/results/` (56797), `/what-our-clients-say/` (56820), `/video-center/` (56893). Scaffold + T1–T11 land here.
- **Batch 2**: `/employment-law/*` children (see inventory.csv, ~12 pages).
- **Batch 3**: `/consumer-rights/*` children (~24).
- **Batch 4**: `/los-angeles/*` (~55, mostly `employment-attorney/*` city pages — one T2-variant with city token).
- **Batch 5**: `/lemon-law/`, `/personal-injury/*`, `/business-litigation/*`, `/property-insurance-claims/*` (~12).
- **Batch 6**: `/chicago/*`, `/cleveland/*`, `/prussia/*` (~8, T11).
- **Batch 7**: utility/legal (privacy, disclaimer, T&Cs, thank-you, sitemap page, white-papers, in-the-news) + attorney profiles from team-sitemap.
- **Blog**: no static pages; T4/T5 + ISR cover all ~800 posts automatically once category slugs verified.

## 8. Working agreement

- One PR per batch to `toddflaw-next`, preview deploy link + 390px screenshots in PR description. Orchestrator (Claude/Josh) reviews against this spec.
- No em/en dashes in copy. One accent color (gold). No new fonts, colors, or corner radii. When the spec and taste conflict, ask in #hermes-channel before building.

## 9. Content Engine (supersedes per-file pages for Batches 3-6; decided 7/26)

One dynamic catch-all route replaces ~100 individual page files:
- `app/[...slug]/page.js` with `generateStaticParams` built from a full WP pages crawl at build time: fetch `/wp-json/wp/v2/pages?per_page=100&page=N&_fields=id,slug,parent,title,excerpt,modified` (paginate to completion), compose each page's FULL path by walking the `parent` chain, and emit params for every path in inventory.csv batches 3-6 (plus any new WP page whose path nests under a known hub).
- Rendering = T2-lite (hero + styled WP content via `rewriteInternalLinks` + breadcrumbs from the parent chain + FAQPage schema on question-styled H2s only + LeadForm CTA). Location hubs (`/los-angeles/`, `/chicago/`, `/cleveland/`, `/prussia/`) keep dedicated T11 files and win over the catch-all.
- Batch 2's 11 static files migrate INTO the engine (delete the per-file routes; verify URL parity before/after). Batches 3-6 then become verification passes (spot-check 3 pages per batch at 390px + parity check of every inventory URL returning 200), not build passes.
- Explicit 404 for paths not in the allowlist (inventory + WP-derived); never render arbitrary slugs.

## 10. Headless WordPress configuration + cutover

**Now (pre-cutover):**
- Next: `app/api/revalidate/route.js` (in repo) — POST `{path, secret}`; verifies `REVALIDATE_SECRET` env; calls `revalidatePath(path)`. Set env in Vercel.
- WP (Hostinger): install mu-plugin `tmf-headless-revalidate.php` (in reference repo) — on `save_post` for posts/pages, POSTs the permalink path to `NEXT_REVALIDATE_URL` with the shared secret. Publish-to-live latency drops from 1h ISR to seconds. Needs wp-admin or Hostinger file manager (Josh or Hostinger connector).
- Rank Math titles/descriptions: expose via REST (`rank_math_title`/`rank_math_description` meta) or fall back to WP title + template description (current behavior). Flag per-page gaps in PR notes rather than inventing.

**Cutover sequence (after Batch 7 + parity):**
1. Hostinger: add `cms.toddflaw.com` as site alias + SSL. 2. Cloudflare: `cms` record → Hostinger origin (added ahead of time; no traffic impact). 3. WP `WP_HOME`/`WP_SITEURL` → `https://cms.toddflaw.com`; verify wp-admin + REST on cms host. 4. `toddflaw-next` env `WP_BASE` → cms origin; add `next.config` rewrite `/wp-content/*` → `https://cms.toddflaw.com/wp-content/*` (post-body media keeps resolving). 5. Vercel: add domain `toddflaw.com` + `www`. 6. Cloudflare: apex/www → Vercel (DNS-only). 7. Verify: 20-URL smoke list, forms, sitemap, robots, GSC fetch. 8. Rollback = revert apex/www records (minutes). Block search indexing of `cms.` (robots + `X-Robots-Tag`).
