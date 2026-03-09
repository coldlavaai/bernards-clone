# Pending Fixes

All fixes completed on 2025-07-15:

1. ✅ Single property page - REVERTED all font-size/letter-spacing/word-spacing/line-height overrides added by previous sub-agents. Page now uses original CSS from property-search.css.
2. ✅ Description text overlapping agent card - Added overflow:hidden and proper margin-bottom to description section and sp-main.
3. ✅ Dark/coloured boxes white text - Added comprehensive !important CSS rules for #5D3384, #1B2A4A, #0B2447 backgrounds, agent buttons, share/heart icons, Book a Valuation, eval CTA, header button.
4. ✅ Property search cards - Confirmed as simple `<a href>` links, no modal code present.

Functional features KEPT:
- Photo lightbox (fullscreen gallery with arrows)
- Request Details inquiry form
- Clickable tel: links on phone numbers
- Book Free Evaluation / Book a Valuation links to /valuations/
- White text contrast fixes on dark backgrounds
