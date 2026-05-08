# Bernards — Change List

**Pivoting to Stage 2:** static-HTML edits paused. Continuing in a fresh Next.js rebuild.

This file records what was shipped into the static site (Stage 1) and what's carried forward to the Next.js build (Stage 2). The static site at bernards.coldlava.ai keeps running until the new build is ready to swap in via domain alias.

**Legend**

- ✅ **Shipped (static)** — live on bernards.coldlava.ai now
- ⏭ **Carry to Next.js** — port to Stage 2, do better
- 🆕 **New for Stage 2** — wasn't in the original list but worth doing
- ❓ **Needs your input/asset** — blocking
- ⏸ **Defer** — parked for later

---

## ✅ Already shipped on the static site

These changes are live and the corresponding static-site decisions carry across to the rebuild as design intent.

| # | Item |
|---|---|
| S1 | Twitter removed from socials |
| S2 | Newsletter → "Follow us on social media" + Facebook/Instagram/LinkedIn icons |
| H1 | Hero CTAs reordered to **BUY / RENT / SELL** |
| H2 | "We don't like to brag but…" heading removed |
| H4 | "We Partner With…" heading hidden |
| H6 | Stats counters (4,000+ properties / £235m / 54 team) repositioned directly below hero |
| H9 | Partner logos shrunk and pushed to bottom-of-page |
| P1 | "Date listed on" removed from property search cards |
| SE1, SE2 | "No sale, no fee" + "Extensive internet advertising" removed |
| SE3 | "Being partner owned and run" → "Partner owned and run" |
| SE4 | "What we do and how we do it" heading added above selling-page dropdowns |
| SE5 | 6-Step Selling Process moved to under hero |
| T1 | Team page slide-in animations removed |
| T2 | Team cards: name/role visible by default |
| C1 | Contact pages: stripped "Telephone:"/"Email:"/"Address:" labels |
| C2 | Contact-us slide-in animations removed |
| design | Brand-coloured social icons (real Instagram gradient), purple "Follow us" band with white text, override stylesheet for site-wide tweaks |
| nav | "Abouts Us" → "About Us"; "Land an New Home" → "Land And New Home"; "– B1" stripped from visible nav across all pages |

**Reverted in Stage 1:** H5 (centred logo with 3 nav each side) — the Elementor static export's nested column markup made the DOM split too brittle. Carry to Stage 2 where the header is one component.

---

## ⏭ Carry to Stage 2 (Next.js rebuild)

Each becomes a clean, native implementation.

### Header & global

| # | Item |
|---|---|
| H5 | Centred bigger logo, 3 nav items each side, real mobile drawer |
| S3 | Futura site-wide as design-tokens font |
| S4 | Purple / white / grey palette as CSS custom properties |

### Home

| # | Item |
|---|---|
| H3 | Google logo → clickable Southsea Google Business Profile |
| H7 | First row "Icons and offices" |
| H8 | Two-column section: Property Search + Carousel |
| H10 | Live Instagram feed (latest 4 posts) replacing partner-with row |
| H11 | Live social-follower counter as part of stats |
| H12 | Houses-sold animated counter (real Bernards rate) |
| H13 | Vebra API live integration |
| H14 | Hero "Google" element → "Follow us on social media" call-out |

### Property Search

| # | Item |
|---|---|
| P2 | Remove banner (featured-over-image) |
| P3 | Apply palette |

### Selling

| # | Item |
|---|---|
| SE6 | Replace dropdowns with 4 icon boxes |
| SE7 | Pull example video from Bernards Instagram (portrait → 2 examples) |

### Landlords

| # | Item |
|---|---|
| L2 | Two new landlord photos |
| L3 | Replace dropdowns with FAQ Q&A |
| L4 | Stats table replacing image (service options + tabbed menu of charges) |
| L5 | Letting agency fee on page |
| L6 | "Process of letting" content section |

### Contact

| # | Item |
|---|---|
| C3 | Branch opening times |

### Team

| # | Item |
|---|---|
| T3 | Search box to filter team by name |

### New page

| # | Item |
|---|---|
| CM1 | Commercial properties in search |
| CM2 | Dedicated Commercial page |

### Defer

| # | Item |
|---|---|
| LN1 | Existing developments under Land And New Home |

---

## 🆕 Picked up for Stage 2

Things the rebuild gives us for free or that should be done while we're rebuilding anyway.

- **Headless CMS layer** so Bernards staff can update copy/staff/news without a dev (Sanity recommended — free tier, generous, native to Next.js)
- **Image optimization** via `next/image` (smaller bundles, automatic AVIF/WebP, lazy-loading)
- **Real responsive nav** (hamburger drawer with focus management, ARIA)
- **Sitemap, robots.txt, OG tags, structured data** generated at build time
- **Edge caching + ISR** so pages are fast globally
- **One source of truth for nav, footer, branch data** — no more copy-paste across 22 pages

---

## ❓ Items requiring your input or assets

Blocking nothing for the scaffold, but each unblocks specific items as we go.

| Asset | Blocks |
|---|---|
| Google Business Profile URL (Southsea) | H3 |
| Vebra credentials | H13 + live property search |
| Bernards annual sales figure + counter baseline | H12 |
| Futura licence (webfont or Adobe Typekit ID) | S3 |
| Two landlord photos | L2 |
| Permission to scrape bernardsestates.co.uk for fee/process/opening hours | L5, L6, C3 |
| Decision: which row becomes 2-col Property Search + Carousel; what's in the carousel | H8 |
| Definition of "Icons and offices" first row | H7 |
| Instagram approach (SnapWidget vs native API) | H10, H11, SE7, H14 |
| CMS decision (Sanity / Payload / no CMS) | content-editing workflow |
| Palette source (sample bernardsestates.co.uk vs hex codes you provide) | S4, all coloured surfaces |

---

## Stage 2 (Next.js rebuild) — high-level plan

1. **Scaffold** new repo `coldlavaai/bernards-next` (or your preferred name); Next.js 16 App Router, TypeScript, Tailwind 4, Vercel project linked
2. **Extract assets** from `~/bernards-clone/wp-content/uploads/` into `public/` of the new app
3. **Build the global shell** — `<Header>`, `<Footer>`, layout, design tokens (CSS variables), Tailwind config
4. **Port pages one at a time** — Home → Property Search → Selling → Landlords → Land & New Home → Meet the Team → Contact → About → Careers → Mortgages
5. **Wire integrations** — Vebra (when creds arrive), Instagram, Google reviews
6. **Add CMS layer** if chosen
7. **QA + perf pass** — Core Web Vitals, accessibility, mobile
8. **Cutover** — point bernards.coldlava.ai alias from old project to new one
