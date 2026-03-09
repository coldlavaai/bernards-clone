# Bernards Estate Agents — WordPress Implementation Guide

**Prepared by:** Cold Lava  
**Date:** July 2026  
**For:** Sam (WordPress/Elementor Developer)  
**Static Demo:** https://bernards-clone.vercel.app

---

## Index

1. [Package Contents](#1-package-contents)
2. [WordPress Plugin Installation](#2-wordpress-plugin-installation)
3. [Property Search Page](#3-property-search-page)
4. [Single Property Page](#4-single-property-page)
5. [Contact Page](#5-contact-page)
6. [Homepage](#6-homepage)
7. [CSS Integration](#7-css-integration)
8. [Property Data Schema](#8-property-data-schema)
9. [Vebra Alto API Integration](#9-vebra-alto-api-integration)
10. [Navigation & URL Structure](#10-navigation--url-structure)
11. [Assets & Dependencies](#11-assets--dependencies)
12. [Implementation Checklist](#12-implementation-checklist)

---

## 1. Package Contents

```
bernards-handover-package.zip
├── HANDOVER-GUIDE.md                        ← This file
├── cold-lava-property-search.zip            ← WordPress plugin (installable zip)
├── cold-lava-property-search/               ← Plugin source (for reference)
│   ├── cold-lava-property-search.php
│   ├── README.md
│   ├── assets/css/
│   │   ├── property-search.css
│   │   └── single-property.css
│   ├── assets/js/
│   │   ├── property-search.js
│   │   └── single-property.js
│   └── includes/
│       ├── class-admin-settings.php
│       ├── class-contact-page.php
│       ├── class-property-search.php
│       ├── class-single-property.php
│       └── class-vebra-api.php
├── assets/
│   ├── css/property-search.css              ← Master CSS (~2,700 lines)
│   └── js/properties.js                     ← Demo property data (12 properties)
├── index.html                               ← Homepage reference
├── property-search-b1/index.html            ← Property search reference
├── single-property/index.html               ← Property detail reference
├── contact-us-b1/index.html                 ← Contact page reference
└── PENDING-FIXES.md
```

The **HTML files** are static reference implementations — use them as a visual guide and source of markup. The **WordPress plugin** is the production implementation.

---

## 2. WordPress Plugin Installation

### Install

1. In WordPress admin, go to **Plugins → Add New → Upload Plugin**
2. Upload `cold-lava-property-search.zip`
3. Click **Install Now**, then **Activate**
4. A new settings page appears at **Settings → Property Search**

### What the Plugin Does

- Registers three shortcodes (see below)
- Enqueues all required CSS and JS automatically
- Connects to the Vebra Alto API for live property data
- Falls back to 12 demo Portsmouth properties when Vebra is not configured
- Caches API responses server-side (15-minute TTL)

### Plugin Settings (Settings → Property Search)

| Setting | Description |
|---------|-------------|
| Data Feed ID | Your Vebra Alto data feed ID |
| Username | Vebra API username |
| Password | Vebra API password |
| Agent Name | Displayed on property cards (e.g. "Bernards Estate Agents, Southsea") |
| Agent Phone | Shown on cards and detail pages |
| Agent Email | Used for enquiry forms |
| Show Placeholders | Toggle demo properties on/off (turn off once Vebra is live) |

### Shortcodes

| Shortcode | Purpose | Suggested Page |
|-----------|---------|----------------|
| `[cl_property_search]` | Full property search with filters, cards, sorting | `/property-search/` |
| `[cl_single_property]` | Property detail with gallery, features, agent card | `/single-property/` |
| `[cl_contact_page]` | Contact page with interactive map and office cards | `/contact-us/` |

**All shortcode pages should use the Elementor Full Width template.**

---

## 3. Property Search Page

### Setup

1. Create a new page: **Property Search**
2. Set the page template to **Elementor Full Width**
3. Add a Shortcode widget containing: `[cl_property_search]`
4. Publish

### What It Renders

**Filter Bar** (sticky, dark background):
- Location text search
- Radius dropdown
- Min/max price dropdowns
- Bedrooms dropdown
- Property type dropdown
- Purple "Search" button

**Property Cards** (list layout):
- Left column (40%): main image, thumbnail strip, badges (FEATURED/REDUCED/NEW HOME), photo count
- Right column (60%): price, address, type badge, bed/bath/reception specs, 2-line description, agent footer
- Each card is a clickable `<a>` link to `/single-property/?id={property_id}`
- Hover effect: subtle lift + image zoom

**Sorting Options:**
- Featured (default), Price Low→High, Price High→Low, Newest, Most Bedrooms

### Key Technical Detail

Property cards use `display: table` layout (not flexbox/grid). This is intentional — it prevents Elementor from overriding the side-by-side image + details layout:

```css
.rm-property-card { display: table !important; table-layout: fixed !important; }
.rm-property-card > .rm-card-images { display: table-cell !important; width: 40% !important; }
.rm-property-card > .rm-card-details { display: table-cell !important; width: 60% !important; }
```

### Reference File

`property-search-b1/index.html` — Contains the full filter bar HTML, `renderCard()` JS function, and filter/sort logic inline (~769 lines).

---

## 4. Single Property Page

### Setup

1. Create a new page: **Single Property** (slug: `single-property`)
2. Set the page template to **Elementor Full Width**
3. Add a Shortcode widget containing: `[cl_single_property]`
4. Publish

The page reads the property ID from the URL query string (`?id=3`).

### What It Renders

- **Photo Gallery**: 60/40 grid layout (main image + 2 side thumbnails). Click any image to open a fullscreen lightbox with arrow-key navigation.
- **Property Header**: Address, property type, price, share/heart icon buttons, badges
- **Info Strip**: Horizontal bar with icons — beds, baths, receptions, sqft, type, tenure
- **Key Features**: Two-column checklist with green ✓ markers
- **Description**: Collapsible "Read more" toggle for long descriptions
- **EPC Rating**: Colour-coded badge (A through G)
- **Agent Card**: Sticky sidebar with logo, phone (clickable `tel:` link), email, "Request Details" button
- **Request Details Modal**: Form collecting Full Name, Phone, Email — shows success confirmation on submit. Wire to your preferred form handler or email in WordPress.
- **Book Free Evaluation CTA**: Purple button linking to `/valuations/`
- **Map**: OpenStreetMap embed (uses property GPS coordinates)
- **Similar Properties**: 3-card grid at bottom

### Lightbox

The lightbox is vanilla JS — no external libraries. Supports:
- Arrow key navigation (left/right)
- Close on Escape or X button
- Swipe on mobile

### Reference File

`single-property/index.html` — Full page markup with inline JS (~815 lines).

---

## 5. Contact Page

### Setup

1. Create or edit the **Contact Us** page
2. Set the page template to **Elementor Full Width**
3. Add a Shortcode widget containing: `[cl_contact_page]`
4. Publish

### What It Renders

**Interactive Map:**
- Uses **Leaflet.js** (loaded from CDN — no API key required)
- Map tiles: OpenStreetMap
- Purple pin markers for all 8 Bernards offices
- Click a marker to see office name, address, and phone number

**Office Cards** (8 branches):

| Office | Phone | Email |
|--------|-------|-------|
| Admiralty Quarter | 023 9200 8575 | southsea@bernardsea.co.uk |
| Southsea | 023 9286 4974 | southsea@bernardsea.co.uk |
| Drayton | 02392 728091 | drayton@bernardsEA.co.uk |
| Fareham | 01329 756500 | fareham@bernardsEA.co.uk |
| Waterlooville | 023 9223 2888 | waterlooville@bernardsea.co.uk |
| Gosport | 02392 0046601 | gosport@bernardsEA.co.uk |
| Lee-On-The-Solent | 02392 553636 | leeonsolent@bernardsea.co.uk |
| Havant | 02392 482 147 | havant@bernardsea.co.uk |

All phone numbers use `<a href="tel:...">` for mobile tap-to-call.

**Inquiry Form:**
- General inquiry form below the office cards
- Collects: Name, Email, Phone, Message, Preferred Branch
- Wire to your preferred form handler in WordPress

### Reference File

`contact-us-b1/index.html` — Full page markup (~863 lines).

---

## 6. Homepage

The homepage is built in Elementor. The following custom sections need to be added to the existing page.

### Search Hero Section

- Dark navy gradient background
- Heading: "Start your search..."
- Three tabs: **Buy** | **Rent** | **Sold**
- Location text input + green SEARCH button
- Form submits to `/property-search/?search={query}&type=buy` (or `rent`/`sold`)
- CSS class: `.bernards-search-hero`

**Implementation:** Copy the hero HTML from `index.html` into an Elementor HTML widget, or rebuild using Elementor widgets styled with the custom CSS.

### Recent Instructions (Property Cards)

- 4 property cards showcasing recent listings
- Each card: image, price, address, bed/bath count, For Sale/To Let badge
- Cards link to single property pages

**Implementation:** Once the Vebra API is connected, these can pull the 4 most recent properties dynamically via the plugin, or be built as static Elementor content.

### Book Free Evaluation CTA

- Navy gradient banner section
- Green CTA button linking to `/valuations/`

### Reference File

`index.html` — Full homepage clone with all sections.

---

## 7. CSS Integration

### Option A: Plugin Handles It (Recommended)

When the `cold-lava-property-search` plugin is active, it automatically enqueues all required CSS for the shortcode pages. No manual CSS work needed for plugin pages.

### Option B: Manual Integration

For sections outside the plugin (e.g. homepage hero, custom Elementor sections), add CSS from `assets/css/property-search.css`:

**Elementor Custom CSS:**  
Appearance → Elementor → Custom CSS → paste relevant sections

**Child Theme:**  
Add to `style.css` in your child theme

**Manual Enqueue:**
```php
function bernards_custom_css() {
    wp_enqueue_style(
        'bernards-property-search',
        get_template_directory_uri() . '/assets/css/property-search.css',
        array(),
        '1.1.0'
    );
}
add_action( 'wp_enqueue_scripts', 'bernards_custom_css' );
```

### Brand Colours (CSS Variables)

```css
:root {
    --rm-green: #00DEB6;        /* Teal/green accent */
    --rm-dark-green: #00b894;   /* Hover state */
    --rm-navy: #0B2447;         /* Primary dark blue */
    --rm-purple: #5D3384;       /* Bernards purple */
    --rm-dark: #2B2B2B;         /* Near black */
    --rm-font: "Montserrat", sans-serif;
}
```

### Elementor Compatibility Notes

- Property cards use `display: table` with `!important` to prevent Elementor from overriding layouts
- Contrast styles on dark backgrounds use `!important` to override Elementor's inline styles
- All custom CSS classes use the `rm-` prefix to avoid conflicts

---

## 8. Property Data Schema

Each property object follows this schema. The plugin maps Vebra API data to this structure automatically. The demo data in `assets/js/properties.js` uses the same schema.

```javascript
{
    id: 1,
    title: "3 bedroom semi-detached house for sale",
    address: "Merton Road, Southsea, Portsmouth, PO5",
    area: "Southsea",
    price: 325000,
    priceDisplay: "£325,000",
    priceLabel: "Guide Price",        // "Guide Price", "Offers Over", etc.
    status: "sale",                   // "sale" or "rent"
    type: "house",                    // "house" or "flat"
    subtype: "Semi-Detached",
    beds: 3,
    baths: 1,
    reception: 2,
    sqft: 1150,
    featured: true,
    badge: "FEATURED",                // "FEATURED", "REDUCED", "NEW HOME", or ""
    dateAdded: "28/06/2025",
    tenure: "Freehold",
    councilTax: "C",
    parking: "Yes",
    garden: "Yes",
    epcRating: "D",                   // A through G
    description: "Short description for card view...",
    fullDescription: [
        "Full paragraph 1...",
        "Full paragraph 2..."
    ],
    keyFeatures: [
        "Feature 1",
        "Feature 2"
    ],
    images: [
        "https://example.com/image1.jpg",
        "https://example.com/image2.jpg"
    ],
    floorplanImages: [],
    lat: 50.7822,
    lng: -1.0750
}
```

### Mapping to WordPress Custom Post Types

If you want to store properties as CPTs instead of (or alongside) the API:

| Property Field | CPT Meta Key (suggested) |
|----------------|--------------------------|
| `price` | `_property_price` |
| `beds` | `_property_bedrooms` |
| `baths` | `_property_bathrooms` |
| `reception` | `_property_receptions` |
| `sqft` | `_property_sqft` |
| `type` | `_property_type` |
| `subtype` | `_property_subtype` |
| `status` | `_property_status` |
| `tenure` | `_property_tenure` |
| `epcRating` | `_property_epc` |
| `lat` / `lng` | `_property_lat` / `_property_lng` |
| `images` | Post gallery / ACF repeater |
| `keyFeatures` | `_property_features` (serialised array) |

The plugin currently pulls data from the Vebra API at runtime. If you need a CPT-based approach, the schema above is the data contract the front-end expects.

---

## 9. Vebra Alto API Integration

### Obtaining Credentials

Contact Vebra Alto support to request API access. You will receive:

- **Data Feed ID** (numeric, e.g. `12345`)
- **API Username**
- **API Password**

These are separate from normal Vebra CRM login credentials.

**Vebra Support:** support@vebra.com

### Configuration

1. Go to **Settings → Property Search** in WordPress admin
2. Enter the three credentials
3. Click **Save Changes**
4. Click **Test Vebra API Connection** to verify

### How It Works

The plugin's `class-vebra-api.php` handles:

1. **Authentication** — Token-based auth with automatic refresh
2. **Data Fetching** — Retrieves branches and properties from the Vebra API
3. **XML Parsing** — Converts Vebra's XML responses into the JS property schema
4. **Caching** — Server-side transient cache (15-minute TTL) for performance
5. **Fallback** — Shows 12 demo Portsmouth properties if the API is not configured or unreachable

### Cache Management

- Cache clears automatically every 15 minutes
- Manual clear: **Settings → Property Search → Clear Property Cache**
- Deactivating the plugin clears all cached data

### Requirements

- PHP 7.4+
- Outbound HTTPS access from the WordPress server (some managed hosts block this)
- WordPress 6.0+

---

## 10. Navigation & URL Structure

### Recommended Page Slugs

The reference HTML files use `-b1` suffixed slugs. Use clean slugs on the live site:

| Page | Slug |
|------|------|
| Property Search | `/property-search/` |
| Single Property | `/single-property/` |
| Contact Us | `/contact-us/` |
| About Us | `/about-us/` |
| Selling | `/selling/` |
| Landlords | `/landlords/` |
| Land & New Homes | `/land-and-new-homes/` |

### Redirects

Set up 301 redirects for any legacy URLs:

| Old URL | Redirect To |
|---------|-------------|
| `/for-sale/` | `/property-search/` |
| `/for-rent/` | `/property-search/?type=rent` |

### Internal Links

The search hero links to: `/property-search/?search={query}&type=buy`  
Property cards link to: `/single-property/?id={property_id}`  
Valuation CTAs link to: `/valuations/`

Update the header and footer navigation to use the final slugs.

---

## 11. Assets & Dependencies

### External Dependencies

| Library | Source | Used By | API Key? |
|---------|--------|---------|----------|
| Leaflet.js | CDN (`unpkg.com/leaflet@1.9.4`) | Contact page map | No |
| OpenStreetMap tiles | `tile.openstreetmap.org` | Contact page + single property map | No |
| Google Fonts (Montserrat) | `fonts.googleapis.com` | All pages | No |

### Images

The demo properties use **Unsplash** placeholder images. These will be replaced by actual property photos from the Vebra API once connected.

Ensure the following Bernards brand assets are in the WordPress Media Library:

- Bernards logo (header): `Bernards-logo-final.png`
- Google Reviews badge: `google-reviews-white.png`
- Office photos (for contact page cards, if desired)
- Homepage background video (if using the video hero)

### Fonts

The design uses **Montserrat** (Google Fonts). The existing WordPress theme likely already loads this. If not:

```html
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
```

### Icon Libraries

- **Font Awesome 5** — Used for misc icons (loaded by Elementor)
- **Custom SVGs** — Bed, bath, reception icons are inline SVGs in the CSS/HTML (no additional icon library needed)

---

## 12. Implementation Checklist

### Phase 1: Plugin Setup
- [ ] Install and activate `cold-lava-property-search.zip`
- [ ] Configure agent details in Settings → Property Search
- [ ] Verify demo properties load on a test page

### Phase 2: Create Pages
- [ ] Create **Property Search** page → `[cl_property_search]` → Elementor Full Width
- [ ] Create **Single Property** page → `[cl_single_property]` → Elementor Full Width
- [ ] Create/update **Contact Us** page → `[cl_contact_page]` → Elementor Full Width
- [ ] Verify all three pages render correctly

### Phase 3: Homepage
- [ ] Add search hero section (from `index.html` reference)
- [ ] Add Recent Instructions property cards section
- [ ] Add Book Free Evaluation CTA banner
- [ ] Link search form to `/property-search/`

### Phase 4: Navigation & URLs
- [ ] Update header nav links to final slugs
- [ ] Update footer nav links to final slugs
- [ ] Set up 301 redirects for old URLs
- [ ] Verify all "Book a Valuation" buttons link to `/valuations/`

### Phase 5: Vebra Alto API
- [ ] Obtain Vebra API credentials
- [ ] Enter credentials in Settings → Property Search
- [ ] Test API connection
- [ ] Disable placeholder properties once live data is confirmed
- [ ] Verify property images load from Vebra

### Phase 6: Testing
- [ ] Desktop: property search filters, sorting, card links
- [ ] Desktop: single property lightbox, inquiry form, agent card
- [ ] Desktop: contact page map, office cards, phone links
- [ ] Mobile/tablet: all pages responsive
- [ ] Cross-browser: Chrome, Firefox, Safari, Edge
- [ ] Performance: page load times acceptable

---

*Prepared by Cold Lava — hello@coldlava.co.uk*
