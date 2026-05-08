/**
 * Cold Lava Property Search - Frontend JavaScript
 *
 * Handles property loading via AJAX, client-side filtering/sorting,
 * card rendering, and single property modal view.
 *
 * @package ColdLavaPropertySearch
 */

(function($) {
    'use strict';

    // ============================================================
    // SVG Icons
    // ============================================================
    var ICO = {
        camera: '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 10.8c-1.77 0-3.2 1.43-3.2 3.2s1.43 3.2 3.2 3.2 3.2-1.43 3.2-3.2-1.43-3.2-3.2-3.2zM22 6h-4.05l-1.83-2H7.88L6.05 6H2C.9 6 0 6.9 0 8v12c0 1.1.9 2 2 2h20c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-10 15c-3.31 0-6-2.69-6-6s2.69-6 6-6 6 2.69 6 6-2.69 6-6 6z"/></svg>',
        bed: '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M7 13c1.66 0 3-1.34 3-3S8.66 7 7 7s-3 1.34-3 3 1.34 3 3 3zm12-6h-8v7H3V5H1v15h2v-3h18v3h2V10c0-2.21-1.79-4-4-4z"/></svg>',
        bath: '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M7 7c0-1.1.9-2 2-2s2 .9 2 2-.9 2-2 2-2-.9-2-2zm13 5H4c-.55 0-1 .45-1 1v2c0 2.76 2.24 5 5 5h8c2.76 0 5-2.24 5-5v-2c0-.55-.45-1-1-1z"/></svg>',
        mail: '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>',
        heart: '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M16.5 3c-1.74 0-3.41.81-4.5 2.09C10.91 3.81 9.24 3 7.5 3 4.42 3 2 5.42 2 8.5c0 3.78 3.4 6.86 8.55 11.54L12 21.35l1.45-1.32C18.6 15.36 22 12.28 22 8.5 22 5.42 19.58 3 16.5 3zm-4.4 15.55l-.1.1-.1-.1C7.14 14.24 4 11.39 4 8.5 4 6.5 5.5 5 7.5 5c1.54 0 3.04.99 3.57 2.36h1.87C13.46 5.99 14.96 5 16.5 5c2 0 3.5 1.5 3.5 3.5 0 2.89-3.14 5.74-7.9 10.05z"/></svg>',
        phone: '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>',
        sqft: '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M19 12h-2v3h-3v2h5v-5zM7 9h3V7H5v5h2V9zm14-6H3c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16.01H3V4.99h18v14.02z"/></svg>',
        back: '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>'
    };

    // ============================================================
    // State
    // ============================================================
    var allProperties = [];
    var dataSource = 'placeholder';
    var searchTimer = null;

    // ============================================================
    // Escape HTML helper
    // ============================================================
    function escHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // ============================================================
    // Format number with commas
    // ============================================================
    function formatNumber(num) {
        return num ? num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '0';
    }

    // ============================================================
    // Render a property card
    // ============================================================
    function renderCard(p) {
        var images = p.images || [];
        var thumbs = images.slice(1, 4);
        var thumbsHtml = '';
        for (var i = 0; i < thumbs.length; i++) {
            thumbsHtml += '<div class="rm-card-thumb"><img src="' + escHtml(thumbs[i]) + '" alt="" loading="lazy" /></div>';
        }

        var badgeHtml = '';
        if (p.badge === 'FEATURED') {
            badgeHtml = '<div class="rm-card-badge rm-card-featured">FEATURED</div>';
        } else if (p.badge === 'REDUCED') {
            badgeHtml = '<div class="rm-card-badge rm-card-reduced">PRICE REDUCED</div>';
        } else if (p.badge === 'NEW HOME') {
            badgeHtml = '<div class="rm-card-badge rm-card-new-home">NEW HOME</div>';
        }

        var imgCount = images.length;
        var sqftHtml = p.sqft ? ICO.sqft + ' <span>' + formatNumber(p.sqft) + ' sq ft</span>' : '';
        var mainImage = images[0] || 'https://images.unsplash.com/photo-1568605114967-8130f3a36994?w=800&h=500&fit=crop';

        var agentName = (clpsData.agentName || 'Estate Agents');

        return '<div class="rm-property-card" data-property-id="' + escHtml(String(p.id)) + '" data-branch-id="' + escHtml(String(p._branchId || '')) + '">' +
            '<div class="rm-card-images">' +
                '<div class="rm-card-main-img">' +
                    '<img src="' + escHtml(mainImage) + '" alt="' + escHtml(p.title) + '" loading="lazy" />' +
                    badgeHtml +
                    '<div class="rm-card-photo-badge">' + ICO.camera + ' <span>' + imgCount + '</span></div>' +
                    '<button class="rm-card-heart-btn clps-heart-btn" title="Save property">' + ICO.heart + '</button>' +
                '</div>' +
                '<div class="rm-card-thumb-col">' + thumbsHtml + '</div>' +
            '</div>' +
            '<div class="rm-card-details">' +
                '<div class="rm-card-price-row">' +
                    '<div class="rm-card-price">' + escHtml(p.priceDisplay || ('£' + formatNumber(p.price || 0))) + '</div>' +
                    '<div class="rm-card-price-label">' + escHtml(p.priceLabel || 'Guide Price') + '</div>' +
                '</div>' +
                '<div class="rm-card-address">' + escHtml(p.address || '') + '</div>' +
                '<div class="rm-card-type-row">' +
                    '<span class="rm-card-subtype">' + escHtml(p.subtype || '') + '</span>' +
                    '<span class="rm-card-specs">' +
                        ICO.bed + ' <span>' + (p.beds || 0) + ' bed' + ((p.beds || 0) !== 1 ? 's' : '') + '</span>' +
                        ICO.bath + ' <span>' + (p.baths || 0) + ' bath' + ((p.baths || 0) !== 1 ? 's' : '') + '</span>' +
                        sqftHtml +
                    '</span>' +
                '</div>' +
                '<div class="rm-card-description">' + escHtml(p.description || '') + '</div>' +
                '<div class="rm-card-footer">' +
                    '<div class="rm-card-agent-row">' +
                        '<div class="rm-card-agent-meta">' +
                            '<span class="rm-card-agent-name">' + escHtml(agentName) + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="rm-card-actions">' +
                        '<span class="rm-card-action-btn rm-card-action-primary">' + ICO.phone + ' Call</span>' +
                        '<span class="rm-card-action-btn">' + ICO.mail + ' Email</span>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
    }

    // ============================================================
    // Apply filters and render
    // ============================================================
    function applyFilters() {
        var filtered = allProperties.slice();

        // Text search
        var searchText = ($('#clps-searchLocation').val() || '').toLowerCase().trim();
        if (searchText) {
            var words = searchText.split(/[\s,]+/).filter(function(w) { return w.length > 0; });
            filtered = filtered.filter(function(p) {
                var haystack = [p.address, p.area, p.title, p.subtype, p.description].join(' ').toLowerCase();
                return words.some(function(w) { return haystack.indexOf(w) !== -1; });
            });
        }

        // Property type
        var type = $('#clps-filterType').val();
        if (type) {
            filtered = filtered.filter(function(p) { return p.type === type; });
        }

        // Price range
        var priceMin = parseInt($('#clps-filterPriceMin').val()) || 0;
        if (priceMin) {
            filtered = filtered.filter(function(p) { return (p.price || 0) >= priceMin; });
        }

        var priceMax = parseInt($('#clps-filterPriceMax').val()) || Infinity;
        if (priceMax !== Infinity) {
            filtered = filtered.filter(function(p) { return (p.price || 0) <= priceMax; });
        }

        // Beds range
        var bedsMin = parseInt($('#clps-filterBedsMin').val()) || 0;
        if (bedsMin) {
            filtered = filtered.filter(function(p) { return (p.beds || 0) >= bedsMin; });
        }

        var bedsMax = parseInt($('#clps-filterBedsMax').val()) || Infinity;
        if (bedsMax !== Infinity) {
            filtered = filtered.filter(function(p) { return (p.beds || 0) <= bedsMax; });
        }

        // Sort
        var sort = $('#clps-sortSelect').val();
        switch (sort) {
            case 'price-asc':
                filtered.sort(function(a, b) { return (a.price || 0) - (b.price || 0); });
                break;
            case 'price-desc':
                filtered.sort(function(a, b) { return (b.price || 0) - (a.price || 0); });
                break;
            case 'newest':
                filtered.sort(function(a, b) {
                    var da = (a.dateAdded || '').split('/').reverse().join('');
                    var db = (b.dateAdded || '').split('/').reverse().join('');
                    return db.localeCompare(da);
                });
                break;
            case 'beds-desc':
                filtered.sort(function(a, b) { return (b.beds || 0) - (a.beds || 0); });
                break;
            case 'featured':
                filtered.sort(function(a, b) { return (b.featured ? 1 : 0) - (a.featured ? 1 : 0); });
                break;
        }

        // Render
        var $list = $('#clps-propertyList');
        if (filtered.length === 0) {
            $list.html(
                '<div class="rm-no-results">' +
                    '<svg viewBox="0 0 24 24" width="48" height="48"><path fill="#ccc" d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>' +
                    '<h3>No properties found</h3>' +
                    '<p>Try adjusting your search criteria or broadening your location to see more results.</p>' +
                '</div>'
            );
        } else {
            var html = '';
            for (var i = 0; i < filtered.length; i++) {
                html += renderCard(filtered[i]);
            }
            $list.html(html);
        }

        // Update count
        $('#clps-resultsCount').html('<strong>' + filtered.length + '</strong> result' + (filtered.length === 1 ? '' : 's'));
    }

    // ============================================================
    // Render single property detail (modal)
    // ============================================================
    function renderPropertyDetail(p) {
        var images = p.images || [];
        var mainImage = images[0] || 'https://images.unsplash.com/photo-1568605114967-8130f3a36994?w=800&h=500&fit=crop';

        // Gallery
        var galleryHtml = '<div class="clps-detail-gallery">';
        galleryHtml += '<div class="clps-detail-gallery-main"><img src="' + escHtml(mainImage) + '" alt="' + escHtml(p.title) + '" /></div>';
        if (images.length > 1) {
            galleryHtml += '<div class="clps-detail-gallery-thumb"><img src="' + escHtml(images[1]) + '" alt="" /></div>';
        }
        if (images.length > 2) {
            galleryHtml += '<div class="clps-detail-gallery-thumb"><img src="' + escHtml(images[2]) + '" alt="" /></div>';
        }
        galleryHtml += '</div>';

        // Badge
        var badgeHtml = '';
        if (p.badge === 'FEATURED') {
            badgeHtml = '<span class="clps-detail-badge clps-badge-featured">FEATURED</span>';
        } else if (p.badge === 'REDUCED') {
            badgeHtml = '<span class="clps-detail-badge clps-badge-reduced">PRICE REDUCED</span>';
        } else if (p.badge === 'NEW HOME') {
            badgeHtml = '<span class="clps-detail-badge clps-badge-new">NEW HOME</span>';
        }

        // Info strip
        var infoStripHtml = '<div class="clps-detail-info-strip">' +
            '<div class="clps-detail-info-item"><div class="clps-detail-info-label">BEDROOMS</div><div class="clps-detail-info-value">' + (p.beds || 0) + '</div></div>' +
            '<div class="clps-detail-info-item"><div class="clps-detail-info-label">BATHROOMS</div><div class="clps-detail-info-value">' + (p.baths || 0) + '</div></div>' +
            '<div class="clps-detail-info-item"><div class="clps-detail-info-label">RECEPTIONS</div><div class="clps-detail-info-value">' + (p.reception || 0) + '</div></div>';
        if (p.sqft) {
            infoStripHtml += '<div class="clps-detail-info-item"><div class="clps-detail-info-label">SIZE</div><div class="clps-detail-info-value">' + formatNumber(p.sqft) + ' sq ft</div></div>';
        }
        infoStripHtml += '</div>';

        // Key features
        var featuresHtml = '';
        var features = p.keyFeatures || [];
        if (features.length > 0) {
            featuresHtml = '<h3 class="clps-detail-section-title">Key Features</h3><ul class="clps-detail-features">';
            for (var i = 0; i < features.length; i++) {
                featuresHtml += '<li>' + escHtml(features[i]) + '</li>';
            }
            featuresHtml += '</ul>';
        }

        // Description
        var descHtml = '';
        var fullDesc = p.fullDescription || [p.description || ''];
        if (fullDesc.length > 0) {
            descHtml = '<h3 class="clps-detail-section-title">Description</h3><div class="clps-detail-description">';
            for (var j = 0; j < fullDesc.length; j++) {
                descHtml += '<p>' + escHtml(fullDesc[j]) + '</p>';
            }
            descHtml += '</div>';
        }

        // Info cards
        var infoCardsHtml = '<div class="clps-detail-info-cards">';
        if (p.tenure) {
            infoCardsHtml += '<div class="clps-detail-info-card"><div class="clps-detail-info-card-label">TENURE</div><div class="clps-detail-info-card-value">' + escHtml(p.tenure) + '</div></div>';
        }
        if (p.councilTax) {
            infoCardsHtml += '<div class="clps-detail-info-card"><div class="clps-detail-info-card-label">COUNCIL TAX</div><div class="clps-detail-info-card-value">Band ' + escHtml(p.councilTax) + '</div></div>';
        }
        if (p.parking) {
            infoCardsHtml += '<div class="clps-detail-info-card"><div class="clps-detail-info-card-label">PARKING</div><div class="clps-detail-info-card-value">' + escHtml(p.parking) + '</div></div>';
        }
        if (p.garden) {
            infoCardsHtml += '<div class="clps-detail-info-card"><div class="clps-detail-info-card-label">GARDEN</div><div class="clps-detail-info-card-value">' + escHtml(p.garden) + '</div></div>';
        }
        infoCardsHtml += '</div>';

        // EPC
        var epcHtml = '';
        if (p.epcRating) {
            var epcClass = 'clps-detail-epc-' + p.epcRating.toLowerCase();
            epcHtml = '<div class="clps-detail-epc">' +
                '<div class="clps-detail-epc-letter ' + epcClass + '">' + escHtml(p.epcRating) + '</div>' +
                '<div class="clps-detail-epc-text">EPC Rating: ' + escHtml(p.epcRating) + '</div>' +
            '</div>';
        }

        // Agent
        var agentName = clpsData.agentName || 'Estate Agents';
        var agentPhone = clpsData.agentPhone || '';
        var agentEmail = clpsData.agentEmail || '';

        var agentHtml = '<div class="clps-detail-agent">' +
            '<div class="clps-detail-agent-name">' + escHtml(agentName) + '</div>' +
            '<div class="clps-detail-agent-btns">';
        if (agentPhone) {
            agentHtml += '<a href="tel:' + escHtml(agentPhone) + '" class="clps-detail-agent-btn clps-detail-agent-btn-primary">' + ICO.phone + ' ' + escHtml(agentPhone) + '</a>';
        }
        if (agentEmail) {
            agentHtml += '<a href="mailto:' + escHtml(agentEmail) + '?subject=Enquiry about ' + encodeURIComponent(p.address || '') + '" class="clps-detail-agent-btn clps-detail-agent-btn-secondary">' + ICO.mail + ' Email Agent</a>';
        }
        agentHtml += '</div></div>';

        return galleryHtml +
            '<div class="clps-detail-body">' +
                badgeHtml +
                '<div class="clps-detail-price">' + escHtml(p.priceDisplay || ('£' + formatNumber(p.price || 0))) + '</div>' +
                '<div class="clps-detail-price-label">' + escHtml(p.priceLabel || 'Guide Price') + '</div>' +
                '<h2 class="clps-detail-address">' + escHtml(p.address || '') + '</h2>' +
                '<div class="clps-detail-type">' + escHtml(p.subtype || '') + ' | Listed on ' + escHtml(p.dateAdded || 'N/A') + '</div>' +
                infoStripHtml +
                featuresHtml +
                descHtml +
                infoCardsHtml +
                epcHtml +
                agentHtml +
            '</div>';
    }

    // ============================================================
    // Open property detail modal
    // ============================================================
    function openPropertyModal(propertyId, branchId) {
        var $modal = $('#clps-propertyModal');
        var $detail = $('#clps-propertyDetail');

        // Show modal with loading
        $modal.show();
        $('body').css('overflow', 'hidden');
        $detail.html(
            '<div class="rm-loading">' +
                '<div class="rm-loading-spinner"></div>' +
                '<p>Loading property details...</p>' +
            '</div>'
        );

        // First try to find in local data
        var found = null;
        for (var i = 0; i < allProperties.length; i++) {
            if (String(allProperties[i].id) === String(propertyId)) {
                found = allProperties[i];
                break;
            }
        }

        if (found) {
            $detail.html(renderPropertyDetail(found));
            return;
        }

        // Fetch via AJAX
        $.ajax({
            url: clpsData.ajaxUrl,
            type: 'GET',
            data: {
                action: 'clps_get_property',
                nonce: clpsData.nonce,
                property_id: propertyId,
                branch_id: branchId || ''
            },
            success: function(response) {
                if (response.success && response.data && response.data.property) {
                    $detail.html(renderPropertyDetail(response.data.property));
                } else {
                    $detail.html(
                        '<div class="rm-no-results">' +
                            '<h3>Property not found</h3>' +
                            '<p>Sorry, we could not load the details for this property.</p>' +
                        '</div>'
                    );
                }
            },
            error: function() {
                $detail.html(
                    '<div class="rm-no-results">' +
                        '<h3>Error loading property</h3>' +
                        '<p>Something went wrong. Please try again.</p>' +
                    '</div>'
                );
            }
        });
    }

    // ============================================================
    // Close modal
    // ============================================================
    function closePropertyModal() {
        $('#clps-propertyModal').hide();
        $('body').css('overflow', '');
    }

    // ============================================================
    // Load properties via AJAX
    // ============================================================
    function loadProperties() {
        $.ajax({
            url: clpsData.ajaxUrl,
            type: 'GET',
            data: {
                action: 'clps_get_properties',
                nonce: clpsData.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    allProperties = response.data.properties || [];
                    dataSource = response.data.source || 'placeholder';
                    applyFilters();
                } else {
                    allProperties = [];
                    applyFilters();
                }
            },
            error: function() {
                allProperties = [];
                applyFilters();
            }
        });
    }

    // ============================================================
    // Initialize
    // ============================================================
    $(document).ready(function() {
        // Only init if our wrapper exists
        if ($('#clps-property-search').length === 0) {
            return;
        }

        // Load properties
        loadProperties();

        // Search button
        $('#clps-searchBtn').on('click', function() {
            applyFilters();
        });

        // Clear search
        $('#clps-clearSearch').on('click', function() {
            $('#clps-searchLocation').val('');
            applyFilters();
        });

        // Live search on typing
        $('#clps-searchLocation').on('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(applyFilters, 300);
        });

        // Enter key on search
        $('#clps-searchLocation').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                applyFilters();
            }
        });

        // Filter change events
        $('#clps-filterRadius, #clps-filterPriceMin, #clps-filterPriceMax, #clps-filterBedsMin, #clps-filterBedsMax, #clps-filterType').on('change', function() {
            applyFilters();
        });

        // Sort change
        $('#clps-sortSelect').on('change', function() {
            applyFilters();
        });

        // Property card click - open modal
        $(document).on('click', '.rm-property-card', function(e) {
            // Don't open modal if clicking heart button
            if ($(e.target).closest('.clps-heart-btn').length) {
                return;
            }
            var propertyId = $(this).data('property-id');
            var branchId = $(this).data('branch-id');
            if (propertyId) {
                openPropertyModal(propertyId, branchId);
            }
        });

        // Heart button toggle
        $(document).on('click', '.clps-heart-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).toggleClass('active');
        });

        // Close modal
        $('#clps-modalClose').on('click', function() {
            closePropertyModal();
        });

        // Close modal on overlay click
        $(document).on('click', '.clps-modal-overlay', function() {
            closePropertyModal();
        });

        // Close modal on Escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                closePropertyModal();
            }
        });

        // Check URL for property ID
        var urlParams = new URLSearchParams(window.location.search);
        var propId = urlParams.get('property');
        var branchParam = urlParams.get('branch');
        if (propId) {
            // Wait for properties to load, then open modal
            var checkInterval = setInterval(function() {
                if (allProperties.length > 0 || dataSource !== 'placeholder') {
                    clearInterval(checkInterval);
                    openPropertyModal(propId, branchParam || '');
                }
            }, 200);
            // Safety timeout
            setTimeout(function() { clearInterval(checkInterval); }, 5000);
        }

        // Set search from URL query
        var q = urlParams.get('q');
        if (q) {
            $('#clps-searchLocation').val(q);
        }
    });

})(jQuery);
