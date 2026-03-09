/**
 * Cold Lava Single Property - Frontend JavaScript
 *
 * Loads a single property via AJAX and renders the full detail page
 * including gallery, key features, description, info cards, map,
 * agent card, and similar properties.
 *
 * @package ColdLavaPropertySearch
 */

(function($) {
    'use strict';

    // SVG Icons
    var SVG = {
        bed: '<svg viewBox="0 0 24 24"><path d="M7 13c1.66 0 3-1.34 3-3S8.66 7 7 7s-3 1.34-3 3 1.34 3 3 3zm12-6h-8v7H3V5H1v15h2v-3h18v3h2V10c0-2.21-1.79-4-4-4z"/></svg>',
        bath: '<svg viewBox="0 0 24 24"><path d="M7 7c0-1.1.9-2 2-2s2 .9 2 2-.9 2-2 2-2-.9-2-2zm13 5H4c-.55 0-1 .45-1 1v2c0 2.76 2.24 5 5 5h8c2.76 0 5-2.24 5-5v-2c0-.55-.45-1-1-1z"/></svg>',
        couch: '<svg viewBox="0 0 24 24"><path d="M21 9V7c0-1.65-1.35-3-3-3H6C4.35 4 3 5.35 3 7v2c-1.65 0-3 1.35-3 3v5h2v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h2v-5c0-1.65-1.35-3-3-3z"/></svg>',
        ruler: '<svg viewBox="0 0 24 24"><path d="M19 12h-2v3h-3v2h5v-5zM7 9h3V7H5v5h2V9zm14-6H3c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16.01H3V4.99h18v14.02z"/></svg>',
        camera: '<svg viewBox="0 0 24 24"><path d="M12 10.8c-1.77 0-3.2 1.43-3.2 3.2s1.43 3.2 3.2 3.2 3.2-1.43 3.2-3.2-1.43-3.2-3.2-3.2zM22 6h-4.05l-1.83-2H7.88L6.05 6H2C.9 6 0 6.9 0 8v12c0 1.1.9 2 2 2h20c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-10 15c-3.31 0-6-2.69-6-6s2.69-6 6-6 6 2.69 6 6-2.69 6-6 6z"/></svg>',
        heart: '<svg viewBox="0 0 24 24"><path d="M16.5 3c-1.74 0-3.41.81-4.5 2.09C10.91 3.81 9.24 3 7.5 3 4.42 3 2 5.42 2 8.5c0 3.78 3.4 6.86 8.55 11.54L12 21.35l1.45-1.32C18.6 15.36 22 12.28 22 8.5 22 5.42 19.58 3 16.5 3z"/></svg>',
        share: '<svg viewBox="0 0 24 24"><path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92-1.31-2.92-2.92-2.92z"/></svg>',
        phone: '<svg viewBox="0 0 24 24"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>',
        mail: '<svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>',
        parking: '<svg viewBox="0 0 24 24"><path d="M13 3H6v18h4v-6h3c3.31 0 6-2.69 6-6s-2.69-6-6-6zm.2 8H10V7h3.2c1.1 0 2 .9 2 2s-.9 2-2 2z"/></svg>',
        garden: '<svg viewBox="0 0 24 24"><path d="M12 22c4.97 0 9-4.03 9-9-4.97 0-9 4.03-9 9zM5.6 10.25c0 1.38 1.12 2.5 2.5 2.5.53 0 1.01-.16 1.42-.44l-.02.19c0 1.38 1.12 2.5 2.5 2.5s2.5-1.12 2.5-2.5l-.02-.19c.4.28.89.44 1.42.44 1.38 0 2.5-1.12 2.5-2.5 0-1-.59-1.85-1.43-2.25.84-.4 1.43-1.25 1.43-2.25 0-1.38-1.12-2.5-2.5-2.5-.53 0-1.01.16-1.42.44l.02-.19C14.5 2.12 13.38 1 12 1S9.5 2.12 9.5 3.5l.02.19c-.4-.28-.89-.44-1.42-.44-1.38 0-2.5 1.12-2.5 2.5 0 1 .59 1.85 1.43 2.25-.84.4-1.43 1.25-1.43 2.25zM12 5.5c1.38 0 2.5 1.12 2.5 2.5s-1.12 2.5-2.5 2.5S9.5 9.38 9.5 8s1.12-2.5 2.5-2.5zM3 13c0 4.97 4.03 9 9 9 0-4.97-4.03-9-9-9z"/></svg>',
        access: '<svg viewBox="0 0 24 24"><path d="M12 4c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm7 14.5c0 .83-.67 1.5-1.5 1.5S16 19.33 16 18.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5z"/></svg>',
        tax: '<svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>'
    };

    function esc(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function fmtNum(n) {
        return n ? n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '0';
    }

    function getPropertyId() {
        var params = new URLSearchParams(window.location.search);
        return params.get('property') || params.get('id') || '';
    }

    function getBranchId() {
        var params = new URLSearchParams(window.location.search);
        return params.get('branch') || '';
    }

    function renderGallery(p) {
        var imgs = p.images || [];
        var main = imgs[0] || 'https://images.unsplash.com/photo-1568605114967-8130f3a36994?w=800&h=500&fit=crop';
        var html = '<div class="sp-gallery-main"><img src="' + esc(main) + '" alt="' + esc(p.title) + '" /></div>';
        html += '<div class="sp-gallery-side">';
        html += '<div class="sp-gallery-thumb"><img src="' + esc(imgs[1] || main) + '" alt="" /></div>';
        html += '<div class="sp-gallery-thumb"><img src="' + esc(imgs[2] || main) + '" alt="" />';
        if (imgs.length > 3) {
            html += '<div class="sp-gallery-photo-count">' + SVG.camera + ' <span>' + imgs.length + ' photos</span></div>';
        }
        html += '</div></div>';
        $('#clps-sp-gallery').html(html);
    }

    function renderHeader(p) {
        var badge = '';
        if (p.badge === 'FEATURED') badge = '<span class="sp-badge sp-badge-featured">FEATURED</span>';
        else if (p.badge === 'REDUCED') badge = '<span class="sp-badge sp-badge-reduced">PRICE REDUCED</span>';
        else if (p.badge === 'NEW HOME') badge = '<span class="sp-badge sp-badge-new">NEW HOME</span>';

        $('#clps-sp-header').html(
            '<div class="sp-header-top"><div>' + badge +
            '<h1 class="sp-header-address">' + esc(p.address) + '</h1>' +
            '<p class="sp-header-type">' + esc(p.title) + '</p></div>' +
            '<div class="sp-header-icons">' +
            '<button class="sp-header-icon-btn" title="Save">' + SVG.heart + '</button>' +
            '<button class="sp-header-icon-btn" title="Share">' + SVG.share + '</button></div></div>' +
            '<div class="sp-header-price-row"><div>' +
            '<p class="sp-price-label">' + esc(p.priceLabel || 'Guide Price') + '</p>' +
            '<p class="sp-price">' + esc(p.priceDisplay) + '</p></div>' +
            '<div class="sp-date-added"><span>Listed on ' + esc(p.dateAdded) + '</span></div></div>'
        );
    }

    function renderInfoStrip(p) {
        $('#clps-sp-info-strip').html(
            '<div class="sp-info-item"><div class="sp-info-item-icon">' + SVG.bed + '</div><p class="sp-info-item-label">Bedrooms</p><p class="sp-info-item-value">' + (p.beds || 0) + '</p></div>' +
            '<div class="sp-info-item"><div class="sp-info-item-icon">' + SVG.bath + '</div><p class="sp-info-item-label">Bathrooms</p><p class="sp-info-item-value">' + (p.baths || 0) + '</p></div>' +
            '<div class="sp-info-item"><div class="sp-info-item-icon">' + SVG.couch + '</div><p class="sp-info-item-label">Receptions</p><p class="sp-info-item-value">' + (p.reception || 0) + '</p></div>' +
            '<div class="sp-info-item"><div class="sp-info-item-icon">' + SVG.ruler + '</div><p class="sp-info-item-label">Size</p><p class="sp-info-item-value">' + (p.sqft ? fmtNum(p.sqft) + ' sq ft' : 'N/A') + '</p></div>' +
            '<div class="sp-info-item"><p class="sp-info-item-label">Property Type</p><p class="sp-info-item-value">' + esc(p.subtype) + '</p></div>' +
            '<div class="sp-info-item"><p class="sp-info-item-label">Tenure</p><p class="sp-info-item-value">' + esc(p.tenure || 'Ask agent') + '</p></div>'
        );
    }

    function renderMain(p) {
        var features = p.keyFeatures || [];
        var featHtml = '';
        if (features.length > 0) {
            featHtml = '<div class="sp-section"><h2 class="sp-section-title">Key Features</h2><ul class="sp-key-features">';
            for (var i = 0; i < features.length; i++) {
                featHtml += '<li>' + esc(features[i]) + '</li>';
            }
            featHtml += '</ul></div>';
        }

        var fullDesc = p.fullDescription || [p.description || ''];
        var descHtml = '<div class="sp-section"><h2 class="sp-section-title">Property Description</h2><div class="sp-description-content">';
        descHtml += '<p>' + esc(fullDesc[0]) + '</p>';
        if (fullDesc.length > 1) {
            descHtml += '<div id="clps-sp-desc-more" style="display:none;">';
            for (var j = 1; j < fullDesc.length; j++) {
                descHtml += '<p>' + esc(fullDesc[j]) + '</p>';
            }
            descHtml += '</div>';
        }
        descHtml += '</div>';
        if (fullDesc.length > 1) {
            descHtml += '<a class="sp-read-more" id="clps-sp-read-more">Read full description ></a>';
        }
        descHtml += '</div>';

        var epcHtml = '';
        if (p.epcRating) {
            epcHtml = '<div class="sp-section"><h2 class="sp-section-title">EPC Rating</h2>' +
                '<div class="sp-epc-badge"><span class="sp-epc-letter sp-epc-' + p.epcRating.toLowerCase() + '">' + esc(p.epcRating) + '</span>' +
                '<span class="sp-epc-text">Energy Performance Certificate - Band ' + esc(p.epcRating) + '</span></div></div>';
        }

        $('#clps-sp-main').html(featHtml + descHtml + epcHtml);
    }

    function renderAgent(p) {
        var name = clpsSingleData.agentName || 'Estate Agents';
        var phone = clpsSingleData.agentPhone || '';
        var email = clpsSingleData.agentEmail || '';
        var logoUrl = clpsSingleData.pluginUrl ? '' : '';

        var html = '<p class="sp-agent-marketed">MARKETED BY</p>' +
            '<div class="sp-agent-name-row"><div><p class="sp-agent-name">' + esc(name) + '</p>' +
            '<p class="sp-agent-address">Southsea, Portsmouth</p></div></div>';

        if (phone) {
            html += '<a href="tel:' + esc(phone.replace(/\s/g, '')) + '" class="sp-agent-btn sp-agent-btn-primary">' + SVG.phone + ' Call agent: ' + esc(phone) + '</a>';
        }
        if (email) {
            html += '<a href="mailto:' + esc(email) + '?subject=Enquiry about ' + encodeURIComponent(p.address || '') + '" class="sp-agent-btn sp-agent-btn-secondary">' + SVG.mail + ' Request details</a>';
        }

        $('#clps-sp-agent').html(html);
    }

    function renderInfoCards(p) {
        $('#clps-sp-info-cards').html(
            '<div class="sp-info-card"><p class="sp-info-card-label">' + SVG.tax + ' Council Tax</p><p class="sp-info-card-value">Band ' + esc(p.councilTax || 'N/A') + '</p></div>' +
            '<div class="sp-info-card"><p class="sp-info-card-label">' + SVG.parking + ' Parking</p><p class="sp-info-card-value">' + esc(p.parking || 'Ask agent') + '</p></div>' +
            '<div class="sp-info-card"><p class="sp-info-card-label">' + SVG.garden + ' Garden</p><p class="sp-info-card-value">' + esc(p.garden || 'Ask agent') + '</p></div>' +
            '<div class="sp-info-card"><p class="sp-info-card-label">' + SVG.access + ' Accessibility</p><p class="sp-info-card-value">' + esc(p.accessibility || 'Ask agent') + '</p></div>'
        );
    }

    function renderMap(p) {
        var mapHtml = '';
        if (p.lat && p.lng) {
            mapHtml = '<div class="sp-map-container"><iframe src="https://www.openstreetmap.org/export/embed.html?bbox=' +
                (p.lng - 0.012) + '%2C' + (p.lat - 0.006) + '%2C' + (p.lng + 0.012) + '%2C' + (p.lat + 0.006) +
                '&layer=mapnik&marker=' + p.lat + '%2C' + p.lng + '" allowfullscreen loading="lazy"></iframe></div>';
        } else {
            mapHtml = '<div class="sp-map-container" style="display:flex;align-items:center;justify-content:center;background:#f4f5f7;"><p style="color:#999;">Map view - ' + esc(p.address) + '</p></div>';
        }
        $('#clps-sp-map').html('<h2 class="sp-section-title">Location</h2><p class="sp-map-address">' + esc(p.address) + '</p>' + mapHtml);
    }

    function renderSimilar(allProperties, currentId) {
        var similar = allProperties.filter(function(s) { return String(s.id) !== String(currentId); }).slice(0, 3);
        if (similar.length === 0) return;

        var searchUrl = clpsSingleData.searchUrl || '/property-search/';
        var html = '';
        for (var i = 0; i < similar.length; i++) {
            var s = similar[i];
            html += '<a class="sp-similar-card" href="?property=' + s.id + '">' +
                '<div class="sp-similar-card-img"><img src="' + esc((s.images || [])[0] || '') + '" alt="' + esc(s.title) + '" loading="lazy" />' +
                '<div class="sp-similar-card-price">' + esc(s.priceDisplay) + '</div></div>' +
                '<div class="sp-similar-card-body"><h3 class="sp-similar-card-address">' + esc(s.address) + '</h3>' +
                '<p class="sp-similar-card-type">' + esc(s.subtype) + ' &middot; ' + (s.beds || 0) + ' bed' + ((s.beds || 0) !== 1 ? 's' : '') + ' &middot; ' + (s.baths || 0) + ' bath' + ((s.baths || 0) !== 1 ? 's' : '') + '</p></div></a>';
        }
        $('#clps-sp-similar').html(html);
    }

    function loadProperty() {
        var propId = getPropertyId();
        var branchId = getBranchId();

        if (!propId) {
            $('#clps-sp-gallery').html('<div class="rm-no-results"><h3>No property selected</h3><p>Please select a property from the search results.</p></div>');
            return;
        }

        // Set back link
        var searchUrl = clpsSingleData.searchUrl || '/property-search/';
        $('#clps-sp-back-link').attr('href', searchUrl);

        // Fetch all properties, then find the one we need
        $.ajax({
            url: clpsSingleData.ajaxUrl,
            type: 'GET',
            data: {
                action: 'clps_get_properties',
                nonce: clpsSingleData.nonce
            },
            success: function(response) {
                if (!response.success || !response.data || !response.data.properties) {
                    showError();
                    return;
                }

                var allProps = response.data.properties || [];
                var found = null;
                for (var i = 0; i < allProps.length; i++) {
                    if (String(allProps[i].id) === String(propId)) {
                        found = allProps[i];
                        break;
                    }
                }

                if (!found) {
                    showError();
                    return;
                }

                // Update page title
                document.title = found.title + ' - ' + found.address;

                // Render all sections
                renderGallery(found);
                renderHeader(found);
                renderInfoStrip(found);
                renderMain(found);
                renderAgent(found);
                renderInfoCards(found);
                renderMap(found);
                renderSimilar(allProps, propId);

                // Bind read more
                $(document).on('click', '#clps-sp-read-more', function(e) {
                    e.preventDefault();
                    $('#clps-sp-desc-more').show();
                    $(this).hide();
                });

                // Bind heart toggle
                $(document).on('click', '.sp-header-icon-btn', function() {
                    $(this).toggleClass('active');
                });
            },
            error: function() {
                showError();
            }
        });
    }

    function showError() {
        $('#clps-sp-gallery').html('<div class="rm-no-results"><h3>Property not found</h3><p>Sorry, we could not load the details for this property.</p></div>');
    }

    $(document).ready(function() {
        if ($('#clps-single-property').length === 0) return;
        loadProperty();
    });

})(jQuery);
