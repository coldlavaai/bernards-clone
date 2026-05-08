# Bernards — Change List

Tracking for the bernards.coldlava.ai overhaul. Ordered easy → complex within each page so we can ship value early without getting blocked on data/access.

**Legend**

- 🟢 **Easy** — copy/HTML/CSS, low risk, ship in minutes
- 🟡 **Medium** — layout/structural, may touch many files
- 🔴 **Complex** — needs data integration, JS feature work, or external API
- ❓ **Needs your input** — ambiguous, decision required, or asset/credential needed
- ⏸ **Defer** — explicitly parked

---

## 🌐 Site-wide

| # | Status | Item | Notes |
|---|---|---|---|
| S1 | 🟢 | Remove Twitter from socials block | `index.html` line ~1384, repeated in footer across all pages |
| S2 | 🟢 | Remove Newsletter widget → replace with "Follow us on social media" call-out + social icons | `index.html` line ~1180; same in every page footer |
| S3 | 🟡 | Set Futura as the site-wide font (case-sensitive use as designed) | Currently mix of Heebo/system. Will need `@font-face` + replace heading/body font stacks across CSS. Need confirmation: licensed Futura webfont or Adobe Typekit ID? |
| S4 | 🔴 | Apply purple / white / grey colour scheme throughout | Need exact hex values (purple primary, accent grey, off-white). Affects buttons, headings, links, backgrounds — large surface area |

---

## 🏠 Home (`index.html`)

| # | Status | Item | Notes |
|---|---|---|---|
| H1 | 🟢 | Change hero "SEARCH" button to "BUY" (keep order Sell / Buy / Rent or your preferred order) | Currently SELL → SEARCH → RENT. Confirm desired order |
| H2 | 🟢 | Remove "We don't like to brag but…" heading | line 976 |
| H3 | 🟢 | Replace "Read Our Reviews" CTA → make Google logo itself clickable, link to Southsea Google profile | ❓ Need: exact Google Business Profile URL for the Southsea branch |
| H4 | 🟢 | Remove "We Partner With…" heading text | line 1112 — content row replaced by Insta feed (H10) |
| H5 | 🟡 | Header: centralise logo, make bigger, 3 nav items each side | Restructures nav into split layout. Needs decision on which 3 items each side |
| H6 | 🟡 | Move stats counters (offices / houses sold / social followers) up — directly below hero banner | Currently lower on page. Reposition the existing ticker section |
| H7 | 🟡 | First row → "Icons and offices" | ❓ Ambiguous. Best guess: a row of office locations each with an icon (Southsea, Drayton, etc.). Confirm exactly what should appear |
| H8 | 🟡 | Two-column section: Property Search (left) / Carousel (right) | ❓ Which existing row gets replaced? And carousel of *what* — featured properties, branch photos, social posts? |
| H9 | 🟡 | Move office logos block → footer, single column, smaller | Easy if it's just the partner logos block (line ~1112+); confirm |
| H10 | 🔴 | "We Partner With" row → live Instagram feed showing latest 4 posts, with new heading | Needs Instagram credentials. Recommended: Instagram Basic Display API token (long-lived) **or** a service like SnapWidget/Curator.io. Confirm approach |
| H11 | 🔴 | Stats ticker — live social media follower count | Needs Instagram + Facebook + (others?) Graph API access. Confirm which networks to count |
| H12 | 🔴 | Stats ticker — "Houses sold" counter that increments +1 every 48 min via CSS/JS | ❓ Need starting baseline number and the implied annual rate (24h / 48min ≈ 30 sales/day, 11k/year — that seems too high; please confirm cadence) |
| H13 | 🔴 | Vebra API — wire up real property feed | Already coded in `api/vebra.js` but in placeholder mode. Needs `VEBRA_DATA_FEED_ID`, `VEBRA_USERNAME`, `VEBRA_PASSWORD` set in Vercel env vars. Once set, search becomes live |
| H14 | 🔴 | "Google" element in hero → swap for "Follow us on social media" call-out | Identify which Google badge in hero, then swap for socials grid |

---

## 🔎 Property Search (`for-sale/`, `for-rent/`, `property-search-b1/`)

| # | Status | Item | Notes |
|---|---|---|---|
| P1 | 🟢 | Remove "Date listed on" line on cards, keep "Branch" | Card template — single CSS rule plus template tweak |
| P2 | 🟡 | Remove banner (featured-over-image) | ❓ Confirm which element — the dark gradient banner over hero image, or the "Featured" badge on listing cards? |
| P3 | 🟡 | Apply purple / white / grey colour scheme | Inherits from S4 — finalise palette there |

---

## 📋 Selling (`selling/`)

| # | Status | Item | Notes |
|---|---|---|---|
| SE1 | 🟢 | Remove "Sale No Fee" | Locate phrase, delete |
| SE2 | 🟢 | Remove "Extensive Internet Advertising" | Same |
| SE3 | 🟢 | Fix "being partner owned and run" → "partner owned and run" | Drop "being" |
| SE4 | 🟢 | Add title above dropdown row → "What we do and how we do it" | New heading element |
| SE5 | 🟡 | Move "6 steps" section up — directly below the hero top row | Reorder |
| SE6 | 🟡 | Replace dropdown row → 4 icon boxes | ❓ Need: 4 icons + 4 headings + 4 short blurbs. Can repurpose dropdown content if titles map cleanly |
| SE7 | 🔴 | Replace example video with one pulled from Bernards Instagram (if portrait, show 2 examples side-by-side) | ❓ Need: Instagram access OR specific video URLs. Portrait 9:16 layout decision |

---

## 🏢 Landlords (`landlords/`)

| # | Status | Item | Notes |
|---|---|---|---|
| L1 | 🟡 | Move "Tailored services" block below the stats block | Reorder existing sections |
| L2 | 🟡 | Add 2 new photos | ❓ Need: image files + decision on placement (replace existing or insert) |
| L3 | 🟡 | Replace dropdowns with FAQ Q&A list | I can draft Q&A from existing site copy + ChatGPT-style suggestions, you approve before publishing |
| L4 | 🔴 | Build a stats table (replacing the stats image) — service options + tabbed menu of changes | ❓ Need clarity: is this a pricing/services comparison table? Need column headings + rows |
| L5 | 🔴 | Display letting agency fee | ❓ Pull from bernardsestates.co.uk — need confirmation I can scrape, or paste the figures here |
| L6 | 🔴 | "Process of letting" — add full explanation section | ❓ Pull from existing live site — confirm scraping access or paste content |

---

## 📞 Contact Us (`contact-us/`)

| # | Status | Item | Notes |
|---|---|---|---|
| C1 | 🟢 | Remove pre-text on contact info — show just number, email, address | Trim labels/sentence intros |
| C2 | 🟡 | Reduce on-scroll motion — turn off slide-in animations | Disable Elementor `data-settings={"_animation":...}` attrs site-wide on this page |
| C3 | 🔴 | Add branch opening times | ❓ Pull from existing site, or paste hours per branch |

---

## 👥 Meet The Team (`meet-the-team/`)

| # | Status | Item | Notes |
|---|---|---|---|
| T1 | 🟢 | Remove slide-in transition | CSS animation removal |
| T2 | 🟡 | Show name/role on the front of each card (no hover required) | Restyle card so info is always visible |
| T3 | 🟡 | Add search box to filter team members by name | Lightweight client-side JS — read all `.team-card` names, filter on input |

---

## 🏘 Land And New Home (`land-an-new-home/`)

| # | Status | Item | Notes |
|---|---|---|---|
| LN1 | ⏸ | Add existing developments | Explicitly parked for later per your note |

---

## 🏬 Commercial (NEW)

| # | Status | Item | Notes |
|---|---|---|---|
| CM1 | 🔴 | Add Commercial properties filter to search | Same setup as residential — needs Vebra (does Vebra feed include commercial listings? to confirm) |
| CM2 | 🔴 | Build Commercial page (mirror of /for-sale layout, commercial properties only) | Defer to Phase 2 if scope is tight |

---

## ❓ Items requiring your input or assets before I can ship

| Ref | What I need |
|---|---|
| H3 | Google Business Profile URL for the Southsea branch |
| H7 | What goes in the "Icons and offices" first row — confirm exact list |
| H8 | Which existing row becomes the 2-column Property Search / Carousel — and what's in the carousel |
| H10, H11, SE7 | Instagram access (long-lived token) **or** decision to use SnapWidget/Curator/etc. |
| H11 | Which social networks contribute to the "follower count" total |
| H12 | Houses-sold counter — starting number + true increment cadence |
| H13 | Vebra credentials (`VEBRA_DATA_FEED_ID`, `VEBRA_USERNAME`, `VEBRA_PASSWORD`) |
| P2 | "Banner" — which element exactly? |
| L2 | Two new landlord photos |
| L4 | Stats table columns/rows + tab structure |
| L5, L6, C3 | Confirm I can scrape bernardsestates.co.uk for fee/process/opening times — or paste content here |
| S3 | Futura licence — webfont ID/file |
| S4 | Exact purple/white/grey hex codes |
| SE6 | 4 icon-box icons + headings + blurbs (or "use dropdown content as-is") |

---

## Suggested execution order

1. **Quick-win sweep** — S1, S2, H1, H2, H4, P1, SE1, SE2, SE3, SE4, T1, C1 (≈30 min, no blockers)
2. **Layout pass** — H5, H6, H9, L1, T2, SE5, C2 (≈1–2 hrs, no blockers)
3. **Content gathered** — once you give me Google URL, fee/hours/process content, photos: H3, L2, L3, L5, L6, C3
4. **Design system** — S3 (Futura), S4 (palette), then P3 inherits
5. **Feature builds** — T3 (team search), SE6 (icon boxes)
6. **Integrations** — H13 (Vebra), H10/H11/SE7 (Instagram), H12 (sold counter)
7. **Net-new pages** — CM1, CM2 (Commercial)
8. **Deferred** — LN1
