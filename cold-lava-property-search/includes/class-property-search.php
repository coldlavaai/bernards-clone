<?php
/**
 * Property Search Frontend
 *
 * Handles the [cl_property_search] shortcode, asset loading,
 * AJAX endpoints for fetching properties, and HTML rendering.
 *
 * @package ColdLavaPropertySearch
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CLPS_Property_Search {

    /**
     * Whether the shortcode is present on the current page.
     *
     * @var bool
     */
    private $shortcode_present = false;

    /**
     * Constructor.
     */
    public function __construct() {
        add_shortcode( 'cl_property_search', array( $this, 'render_shortcode' ) );

        // AJAX endpoints (logged in and not logged in)
        add_action( 'wp_ajax_clps_get_properties', array( $this, 'ajax_get_properties' ) );
        add_action( 'wp_ajax_nopriv_clps_get_properties', array( $this, 'ajax_get_properties' ) );
        add_action( 'wp_ajax_clps_get_property', array( $this, 'ajax_get_property' ) );
        add_action( 'wp_ajax_nopriv_clps_get_property', array( $this, 'ajax_get_property' ) );

        // Conditional asset loading
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Enqueue CSS and JS only on pages with the shortcode.
     */
    public function enqueue_assets() {
        global $post;

        // Check if the current page/post contains our shortcode
        if ( ! is_a( $post, 'WP_Post' ) ) {
            return;
        }

        if ( ! has_shortcode( $post->post_content, 'cl_property_search' ) ) {
            // Also check for Elementor - shortcode may be in widgets
            $elementor_data = get_post_meta( $post->ID, '_elementor_data', true );
            if ( empty( $elementor_data ) || strpos( $elementor_data, 'cl_property_search' ) === false ) {
                return;
            }
        }

        $this->shortcode_present = true;

        // Google Fonts - Montserrat
        wp_enqueue_style(
            'clps-google-fonts',
            'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap',
            array(),
            CLPS_VERSION
        );

        // Plugin CSS
        wp_enqueue_style(
            'clps-property-search',
            CLPS_PLUGIN_URL . 'assets/css/property-search.css',
            array(),
            CLPS_VERSION
        );

        // Plugin JS
        wp_enqueue_script(
            'clps-property-search',
            CLPS_PLUGIN_URL . 'assets/js/property-search.js',
            array( 'jquery' ),
            CLPS_VERSION,
            true
        );

        // Pass data to JS
        wp_localize_script( 'clps-property-search', 'clpsData', array(
            'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
            'nonce'             => wp_create_nonce( 'clps_frontend_nonce' ),
            'pluginUrl'         => CLPS_PLUGIN_URL,
            'showPlaceholders'  => get_option( 'clps_show_placeholders', '1' ),
            'agentName'         => get_option( 'clps_agent_name', '' ),
            'agentPhone'        => get_option( 'clps_agent_phone', '' ),
            'agentEmail'        => get_option( 'clps_agent_email', '' ),
        ) );
    }

    /**
     * Render the [cl_property_search] shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( array(), $atts, 'cl_property_search' );

        ob_start();
        $this->render_search_page();
        return ob_get_clean();
    }

    /**
     * AJAX handler: Get all properties.
     */
    public function ajax_get_properties() {
        check_ajax_referer( 'clps_frontend_nonce', 'nonce' );

        $api = new CLPS_Vebra_API();

        if ( $api->is_configured() ) {
            $properties = $api->get_properties();
            if ( ! empty( $properties ) ) {
                wp_send_json_success( array(
                    'source'     => 'vebra',
                    'configured' => true,
                    'properties' => $properties,
                ) );
            }
        }

        // Not configured or no results - return placeholders if enabled
        $show_placeholders = get_option( 'clps_show_placeholders', '1' );

        wp_send_json_success( array(
            'source'     => 'placeholder',
            'configured' => $api->is_configured(),
            'properties' => ( '1' === $show_placeholders ) ? $this->get_placeholder_properties() : array(),
        ) );
    }

    /**
     * AJAX handler: Get a single property.
     */
    public function ajax_get_property() {
        check_ajax_referer( 'clps_frontend_nonce', 'nonce' );

        $property_id = isset( $_GET['property_id'] ) ? sanitize_text_field( wp_unslash( $_GET['property_id'] ) ) : '';
        $branch_id   = isset( $_GET['branch_id'] ) ? sanitize_text_field( wp_unslash( $_GET['branch_id'] ) ) : '';

        if ( empty( $property_id ) ) {
            wp_send_json_error( array( 'message' => 'Missing property ID.' ) );
        }

        $api = new CLPS_Vebra_API();

        if ( $api->is_configured() ) {
            $property = $api->get_property( $property_id, $branch_id );
            if ( $property ) {
                wp_send_json_success( array(
                    'source'   => 'vebra',
                    'property' => $property,
                ) );
            }
        }

        // Try placeholder properties
        $placeholders = $this->get_placeholder_properties();
        foreach ( $placeholders as $p ) {
            if ( (string) $p['id'] === (string) $property_id ) {
                wp_send_json_success( array(
                    'source'   => 'placeholder',
                    'property' => $p,
                ) );
            }
        }

        wp_send_json_error( array( 'message' => 'Property not found.' ) );
    }

    // ────────────────────────────────────────────
    // HTML Rendering
    // ────────────────────────────────────────────

    /**
     * Render the full property search page HTML.
     */
    private function render_search_page() {
        ?>
        <div id="clps-property-search" class="clps-wrapper">

            <!-- Filter Bar -->
            <div class="rm-filter-bar">
                <div class="rm-filter-bar-inner">
                    <div class="rm-filter-location">
                        <svg class="rm-location-icon" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                        <input type="text" id="clps-searchLocation" placeholder="<?php echo esc_attr( 'e.g. Portsmouth, Southsea, PO5...' ); ?>" />
                        <button class="rm-clear-btn" id="clps-clearSearch" title="<?php echo esc_attr( 'Clear' ); ?>">&#10005;</button>
                    </div>

                    <select class="rm-filter-select" id="clps-filterRadius">
                        <option value="10" selected><?php echo esc_html( 'Within 10 miles' ); ?></option>
                        <option value="0.25"><?php echo esc_html( 'Within 1/4 mile' ); ?></option>
                        <option value="0.5"><?php echo esc_html( 'Within 1/2 mile' ); ?></option>
                        <option value="1"><?php echo esc_html( 'Within 1 mile' ); ?></option>
                        <option value="3"><?php echo esc_html( 'Within 3 miles' ); ?></option>
                        <option value="5"><?php echo esc_html( 'Within 5 miles' ); ?></option>
                        <option value="15"><?php echo esc_html( 'Within 15 miles' ); ?></option>
                        <option value="20"><?php echo esc_html( 'Within 20 miles' ); ?></option>
                        <option value="30"><?php echo esc_html( 'Within 30 miles' ); ?></option>
                    </select>

                    <select class="rm-filter-select" id="clps-filterPriceMin">
                        <option value=""><?php echo esc_html( 'Min Price' ); ?></option>
                        <option value="50000"><?php echo esc_html( '£50,000' ); ?></option>
                        <option value="100000"><?php echo esc_html( '£100,000' ); ?></option>
                        <option value="150000"><?php echo esc_html( '£150,000' ); ?></option>
                        <option value="200000"><?php echo esc_html( '£200,000' ); ?></option>
                        <option value="250000"><?php echo esc_html( '£250,000' ); ?></option>
                        <option value="300000"><?php echo esc_html( '£300,000' ); ?></option>
                        <option value="350000"><?php echo esc_html( '£350,000' ); ?></option>
                        <option value="400000"><?php echo esc_html( '£400,000' ); ?></option>
                        <option value="500000"><?php echo esc_html( '£500,000' ); ?></option>
                        <option value="600000"><?php echo esc_html( '£600,000' ); ?></option>
                        <option value="750000"><?php echo esc_html( '£750,000' ); ?></option>
                    </select>

                    <span class="rm-filter-to"><?php echo esc_html( 'to' ); ?></span>

                    <select class="rm-filter-select" id="clps-filterPriceMax">
                        <option value=""><?php echo esc_html( 'Max Price' ); ?></option>
                        <option value="150000"><?php echo esc_html( '£150,000' ); ?></option>
                        <option value="200000"><?php echo esc_html( '£200,000' ); ?></option>
                        <option value="250000"><?php echo esc_html( '£250,000' ); ?></option>
                        <option value="300000"><?php echo esc_html( '£300,000' ); ?></option>
                        <option value="400000"><?php echo esc_html( '£400,000' ); ?></option>
                        <option value="500000"><?php echo esc_html( '£500,000' ); ?></option>
                        <option value="650000"><?php echo esc_html( '£650,000' ); ?></option>
                        <option value="800000"><?php echo esc_html( '£800,000' ); ?></option>
                        <option value="1000000"><?php echo esc_html( '£1,000,000' ); ?></option>
                        <option value="1500000"><?php echo esc_html( '£1,500,000' ); ?></option>
                    </select>

                    <select class="rm-filter-select" id="clps-filterBedsMin">
                        <option value=""><?php echo esc_html( 'Min Beds' ); ?></option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>

                    <span class="rm-filter-to"><?php echo esc_html( 'to' ); ?></span>

                    <select class="rm-filter-select" id="clps-filterBedsMax">
                        <option value=""><?php echo esc_html( 'Max Beds' ); ?></option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6"><?php echo esc_html( '6+' ); ?></option>
                    </select>

                    <select class="rm-filter-select" id="clps-filterType">
                        <option value=""><?php echo esc_html( 'Property Type' ); ?></option>
                        <option value="house"><?php echo esc_html( 'Houses' ); ?></option>
                        <option value="flat"><?php echo esc_html( 'Flats / Apartments' ); ?></option>
                        <option value="bungalow"><?php echo esc_html( 'Bungalows' ); ?></option>
                    </select>

                    <button class="rm-filter-btn" id="clps-searchBtn">
                        <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                        <?php echo esc_html( 'Search' ); ?>
                    </button>
                </div>
            </div>

            <!-- Results Bar -->
            <div class="rm-results-bar">
                <div class="rm-results-bar-inner">
                    <div class="rm-results-left">
                        <div class="rm-results-count" id="clps-resultsCount"><strong>0</strong> results</div>
                        <div class="rm-results-subtitle"><?php echo esc_html( 'Properties For Sale' ); ?></div>
                    </div>
                    <div class="rm-results-right">
                        <div class="rm-sort-controls">
                            <label for="clps-sortSelect"><?php echo esc_html( 'Sort:' ); ?></label>
                            <select class="rm-sort-select" id="clps-sortSelect">
                                <option value="featured"><?php echo esc_html( 'Featured' ); ?></option>
                                <option value="price-desc"><?php echo esc_html( 'Highest Price' ); ?></option>
                                <option value="price-asc"><?php echo esc_html( 'Lowest Price' ); ?></option>
                                <option value="newest"><?php echo esc_html( 'Newest Listed' ); ?></option>
                                <option value="beds-desc"><?php echo esc_html( 'Most Bedrooms' ); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="rm-main-layout">
                <div class="rm-property-list" id="clps-propertyList">
                    <div class="rm-loading" id="clps-loadingState">
                        <div class="rm-loading-spinner"></div>
                        <p><?php echo esc_html( 'Loading properties...' ); ?></p>
                    </div>
                </div>

                <div class="rm-sidebar">
                    <div class="rm-sidebar-map">
                        <div class="rm-sidebar-map-container">
                            <iframe src="https://www.openstreetmap.org/export/embed.html?bbox=-1.21%2C50.77%2C-0.97%2C50.92&amp;layer=mapnik" loading="lazy" title="<?php echo esc_attr( 'Map' ); ?>"></iframe>
                        </div>

                        <div class="rm-sidebar-cta-card">
                            <div class="rm-sidebar-cta-icon">
                                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                            </div>
                            <p class="rm-sidebar-cta-title"><?php echo esc_html( 'Thinking of selling?' ); ?></p>
                            <p class="rm-sidebar-cta-text"><?php echo esc_html( 'Get a free property valuation from our experienced local team.' ); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Single Property Modal -->
            <div id="clps-propertyModal" class="clps-modal" style="display:none;">
                <div class="clps-modal-overlay"></div>
                <div class="clps-modal-content">
                    <button class="clps-modal-close" id="clps-modalClose" title="<?php echo esc_attr( 'Close' ); ?>">&times;</button>
                    <div id="clps-propertyDetail" class="clps-property-detail">
                        <div class="rm-loading">
                            <div class="rm-loading-spinner"></div>
                            <p><?php echo esc_html( 'Loading property details...' ); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer branding -->
            <div class="clps-footer-branding">
                <p><?php echo esc_html( 'Powered by Cold Lava' ); ?></p>
            </div>

        </div>
        <?php
    }

    // ────────────────────────────────────────────
    // Placeholder Properties
    // ────────────────────────────────────────────

    /**
     * Get placeholder properties for demo/preview.
     *
     * @return array Array of property data.
     */
    private function get_placeholder_properties() {
        return array(
            array(
                'id'              => 1,
                'title'           => '3 bedroom semi-detached house for sale',
                'address'         => 'Merton Road, Southsea, Portsmouth, PO5',
                'area'            => 'Southsea',
                'price'           => 325000,
                'priceDisplay'    => '£325,000',
                'priceLabel'      => 'Guide Price',
                'status'          => 'sale',
                'type'            => 'house',
                'subtype'         => 'Semi-Detached',
                'beds'            => 3,
                'baths'           => 1,
                'reception'       => 2,
                'sqft'            => 1150,
                'featured'        => true,
                'badge'           => 'FEATURED',
                'dateAdded'       => '28/06/2025',
                'tenure'          => 'Freehold',
                'councilTax'      => 'C',
                'parking'         => 'Yes',
                'garden'          => 'Yes',
                'accessibility'   => 'Ask agent',
                'epcRating'       => 'D',
                'distance'        => '1.20',
                'description'     => 'A beautifully presented three-bedroom semi-detached house in the heart of Southsea. Offering spacious living accommodation with a modern kitchen, two reception rooms, and a lovely south-facing garden.',
                'fullDescription' => array( 'A beautifully presented three-bedroom semi-detached house in the heart of Southsea. Offering spacious living accommodation with a modern kitchen, two reception rooms, and a lovely south-facing garden. Moments from the seafront and Albert Road amenities.' ),
                'keyFeatures'     => array( 'Three bedrooms', 'South-facing garden', 'Modern kitchen', 'Two reception rooms', 'Close to seafront', 'Freehold' ),
                'images'          => array(
                    'https://images.unsplash.com/photo-1568605114967-8130f3a36994?w=800&h=500&fit=crop',
                    'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800&h=500&fit=crop',
                    'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=800&h=500&fit=crop',
                    'https://images.unsplash.com/photo-1616137466211-f939a420be84?w=800&h=500&fit=crop',
                ),
                'floorplanImages' => array(),
                'lat'             => 50.7871,
                'lng'             => -1.0836,
                '_branchId'       => '',
            ),
            array(
                'id'              => 2,
                'title'           => '4 bedroom detached house for sale',
                'address'         => 'Highlands Road, Fareham, Hampshire, PO16',
                'area'            => 'Fareham',
                'price'           => 475000,
                'priceDisplay'    => '£475,000',
                'priceLabel'      => 'Guide Price',
                'status'          => 'sale',
                'type'            => 'house',
                'subtype'         => 'Detached',
                'beds'            => 4,
                'baths'           => 2,
                'reception'       => 2,
                'sqft'            => 1680,
                'featured'        => true,
                'badge'           => 'FEATURED',
                'dateAdded'       => '25/06/2025',
                'tenure'          => 'Freehold',
                'councilTax'      => 'E',
                'parking'         => 'Yes',
                'garden'          => 'Yes',
                'accessibility'   => 'Ask agent',
                'epcRating'       => 'C',
                'distance'        => '8.50',
                'description'     => 'An impressive four-bedroom detached family home set in a generous plot in Fareham. Featuring a double garage, en-suite to master, conservatory, and beautifully landscaped gardens.',
                'fullDescription' => array( 'An impressive four-bedroom detached family home set in a generous plot in Fareham. Featuring a double garage, en-suite to master, conservatory, and beautifully landscaped gardens. Excellent transport links to the M27 and direct trains to London Waterloo.' ),
                'keyFeatures'     => array( 'Four bedrooms', 'Double garage', 'En-suite to master', 'Landscaped gardens', 'Conservatory', 'Close to M27' ),
                'images'          => array(
                    'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800&h=500&fit=crop',
                    'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=800&h=500&fit=crop',
                    'https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?w=800&h=500&fit=crop',
                    'https://images.unsplash.com/photo-1600585154526-990dced4db0d?w=800&h=500&fit=crop',
                ),
                'floorplanImages' => array(),
                'lat'             => 50.8521,
                'lng'             => -1.1783,
                '_branchId'       => '',
            ),
            array(
                'id'              => 3,
                'title'           => '2 bedroom flat for sale',
                'address'         => 'The Boardwalk, Gunwharf Quays, Portsmouth, PO1',
                'area'            => 'Portsmouth',
                'price'           => 285000,
                'priceDisplay'    => '£285,000',
                'priceLabel'      => 'Offers Over',
                'status'          => 'sale',
                'type'            => 'flat',
                'subtype'         => 'Purpose Built Flat',
                'beds'            => 2,
                'baths'           => 1,
                'reception'       => 1,
                'sqft'            => 780,
                'featured'        => false,
                'badge'           => '',
                'dateAdded'       => '20/06/2025',
                'tenure'          => 'Leasehold',
                'councilTax'      => 'C',
                'parking'         => 'Yes',
                'garden'          => 'No',
                'accessibility'   => 'Lift access',
                'epcRating'       => 'B',
                'distance'        => '0.50',
                'description'     => 'A stunning two-bedroom waterfront apartment at the prestigious Gunwharf Quays development with harbour views, allocated parking, and access to residents\' facilities.',
                'fullDescription' => array( 'A stunning two-bedroom waterfront apartment at the prestigious Gunwharf Quays development with harbour views, allocated parking, and access to residents\' facilities. Walking distance to Portsmouth Harbour station.' ),
                'keyFeatures'     => array( 'Two bedrooms', 'Harbour views', 'Allocated parking', 'Lift access', 'Residents\' facilities', 'Leasehold' ),
                'images'          => array(
                    'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=800&h=500&fit=crop',
                    'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=800&h=500&fit=crop',
                    'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800&h=500&fit=crop',
                    'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=800&h=500&fit=crop',
                ),
                'floorplanImages' => array(),
                'lat'             => 50.7944,
                'lng'             => -1.1077,
                '_branchId'       => '',
            ),
            array(
                'id'              => 4,
                'title'           => '5 bedroom detached house for sale',
                'address'         => 'Anglesey Road, Alverstoke, Gosport, PO12',
                'area'            => 'Alverstoke',
                'price'           => 695000,
                'priceDisplay'    => '£695,000',
                'priceLabel'      => 'Guide Price',
                'status'          => 'sale',
                'type'            => 'house',
                'subtype'         => 'Detached',
                'beds'            => 5,
                'baths'           => 3,
                'reception'       => 3,
                'sqft'            => 2650,
                'featured'        => true,
                'badge'           => 'FEATURED',
                'dateAdded'       => '15/06/2025',
                'tenure'          => 'Freehold',
                'councilTax'      => 'G',
                'parking'         => 'Yes',
                'garden'          => 'Yes',
                'accessibility'   => 'Ask agent',
                'epcRating'       => 'C',
                'distance'        => '4.20',
                'description'     => 'A magnificent five-bedroom detached residence in the exclusive Alverstoke area of Gosport. Set within landscaped grounds approaching half an acre with a double garage, swimming pool, and panoramic Solent views.',
                'fullDescription' => array( 'A magnificent five-bedroom detached residence in the exclusive Alverstoke area of Gosport. Set within landscaped grounds approaching half an acre with a double garage, swimming pool, and panoramic Solent views.' ),
                'keyFeatures'     => array( 'Five bedrooms', 'Swimming pool', 'Solent views', 'Half acre plot', 'Double garage', 'Three reception rooms' ),
                'images'          => array(
                    'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=800&h=500&fit=crop',
                    'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=800&h=500&fit=crop',
                    'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=800&h=500&fit=crop',
                    'https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?w=800&h=500&fit=crop',
                ),
                'floorplanImages' => array(),
                'lat'             => 50.7804,
                'lng'             => -1.1592,
                '_branchId'       => '',
            ),
            array(
                'id'              => 5,
                'title'           => '3 bedroom terraced house for sale',
                'address'         => 'Pembroke Road, Old Portsmouth, PO1',
                'area'            => 'Old Portsmouth',
                'price'           => 410000,
                'priceDisplay'    => '£410,000',
                'priceLabel'      => 'Guide Price',
                'status'          => 'sale',
                'type'            => 'house',
                'subtype'         => 'Terraced',
                'beds'            => 3,
                'baths'           => 2,
                'reception'       => 2,
                'sqft'            => 1320,
                'featured'        => false,
                'badge'           => '',
                'dateAdded'       => '22/06/2025',
                'tenure'          => 'Freehold',
                'councilTax'      => 'D',
                'parking'         => 'No',
                'garden'          => 'Yes',
                'accessibility'   => 'Ask agent',
                'epcRating'       => 'D',
                'distance'        => '0.80',
                'description'     => 'A beautifully renovated three-bedroom Georgian terrace in the historic heart of Old Portsmouth. Retaining many original features including sash windows and exposed brickwork.',
                'fullDescription' => array( 'A beautifully renovated three-bedroom Georgian terrace in the historic heart of Old Portsmouth. Retaining many original features including sash windows and exposed brickwork, with a contemporary kitchen/dining extension.' ),
                'keyFeatures'     => array( 'Three bedrooms', 'Georgian terrace', 'Original features', 'Kitchen extension', 'Garden', 'Historic location' ),
                'images'          => array(
                    'https://images.unsplash.com/photo-1558036117-15d82a90b9b1?w=800&h=500&fit=crop',
                    'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=800&h=500&fit=crop',
                    'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800&h=500&fit=crop',
                    'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=800&h=500&fit=crop',
                ),
                'floorplanImages' => array(),
                'lat'             => 50.7894,
                'lng'             => -1.1063,
                '_branchId'       => '',
            ),
            array(
                'id'              => 6,
                'title'           => '1 bedroom flat for sale',
                'address'         => 'Palmerston Road, Southsea, Portsmouth, PO4',
                'area'            => 'Southsea',
                'price'           => 175000,
                'priceDisplay'    => '£175,000',
                'priceLabel'      => 'Guide Price',
                'status'          => 'sale',
                'type'            => 'flat',
                'subtype'         => 'Converted Flat',
                'beds'            => 1,
                'baths'           => 1,
                'reception'       => 1,
                'sqft'            => 480,
                'featured'        => false,
                'badge'           => '',
                'dateAdded'       => '18/06/2025',
                'tenure'          => 'Leasehold',
                'councilTax'      => 'A',
                'parking'         => 'No',
                'garden'          => 'No',
                'accessibility'   => 'Ask agent',
                'epcRating'       => 'C',
                'distance'        => '1.50',
                'description'     => 'A stylish one-bedroom first-floor flat in a converted Victorian building on Southsea\'s popular Palmerston Road. Recently refurbished with a modern kitchen and bathroom.',
                'fullDescription' => array( 'A stylish one-bedroom first-floor flat in a converted Victorian building on Southsea\'s popular Palmerston Road. Recently refurbished with a modern kitchen and bathroom. Ideal first home or buy-to-let investment.' ),
                'keyFeatures'     => array( 'One bedroom', 'Recently refurbished', 'Victorian conversion', 'Modern kitchen', 'Popular location', 'Investment potential' ),
                'images'          => array(
                    'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800&h=500&fit=crop',
                    'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=800&h=500&fit=crop',
                    'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800&h=500&fit=crop',
                    'https://images.unsplash.com/photo-1560185127-6ed189bf02f4?w=800&h=500&fit=crop',
                ),
                'floorplanImages' => array(),
                'lat'             => 50.7842,
                'lng'             => -1.0874,
                '_branchId'       => '',
            ),
            array(
                'id'              => 7,
                'title'           => '4 bedroom semi-detached house for sale',
                'address'         => 'Solent Road, Drayton, Portsmouth, PO6',
                'area'            => 'Drayton',
                'price'           => 385000,
                'priceDisplay'    => '£385,000',
                'priceLabel'      => 'Guide Price',
                'status'          => 'sale',
                'type'            => 'house',
                'subtype'         => 'Semi-Detached',
                'beds'            => 4,
                'baths'           => 2,
                'reception'       => 2,
                'sqft'            => 1450,
                'featured'        => false,
                'badge'           => 'REDUCED',
                'dateAdded'       => '10/06/2025',
                'tenure'          => 'Freehold',
                'councilTax'      => 'D',
                'parking'         => 'Yes',
                'garden'          => 'Yes',
                'accessibility'   => 'Ask agent',
                'epcRating'       => 'C',
                'distance'        => '5.10',
                'description'     => 'A spacious four-bedroom semi-detached family home in the popular Drayton area. Extended to the rear with a large kitchen/family room, utility room, and generous gardens.',
                'fullDescription' => array( 'A spacious four-bedroom semi-detached family home in the popular Drayton area. Extended to the rear with a large kitchen/family room, utility room, and generous gardens. Close to excellent schools.' ),
                'keyFeatures'     => array( 'Four bedrooms', 'Extended kitchen', 'Utility room', 'Generous gardens', 'Close to schools', 'Price reduced' ),
                'images'          => array(
                    'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800&h=500&fit=crop',
                    'https://images.unsplash.com/photo-1568605114967-8130f3a36994?w=800&h=500&fit=crop',
                    'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=800&h=500&fit=crop',
                    'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=800&h=500&fit=crop',
                ),
                'floorplanImages' => array(),
                'lat'             => 50.8376,
                'lng'             => -1.0543,
                '_branchId'       => '',
            ),
            array(
                'id'              => 8,
                'title'           => '6 bedroom detached house for sale',
                'address'         => 'Wickham Road, Wickham, Hampshire, PO17',
                'area'            => 'Wickham',
                'price'           => 850000,
                'priceDisplay'    => '£850,000',
                'priceLabel'      => 'Guide Price',
                'status'          => 'sale',
                'type'            => 'house',
                'subtype'         => 'Detached',
                'beds'            => 6,
                'baths'           => 4,
                'reception'       => 3,
                'sqft'            => 3800,
                'featured'        => true,
                'badge'           => 'NEW HOME',
                'dateAdded'       => '01/07/2025',
                'tenure'          => 'Freehold',
                'councilTax'      => 'G',
                'parking'         => 'Yes',
                'garden'          => 'Yes',
                'accessibility'   => 'Ask agent',
                'epcRating'       => 'B',
                'distance'        => '12.00',
                'description'     => 'A stunning six-bedroom executive detached house in the sought-after village of Wickham. Newly built to an exceptional specification with underfloor heating, bespoke Neptune kitchen, triple garage, and landscaped grounds.',
                'fullDescription' => array( 'A stunning six-bedroom executive detached house in the sought-after village of Wickham. Newly built to an exceptional specification with underfloor heating, bespoke Neptune kitchen, triple garage, and landscaped grounds of approximately one acre.' ),
                'keyFeatures'     => array( 'Six bedrooms', 'Newly built', 'Neptune kitchen', 'Triple garage', 'One acre grounds', 'Underfloor heating' ),
                'images'          => array(
                    'https://images.unsplash.com/photo-1613977257363-707ba9348227?w=800&h=500&fit=crop',
                    'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=800&h=500&fit=crop',
                    'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=800&h=500&fit=crop',
                    'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=800&h=500&fit=crop',
                ),
                'floorplanImages' => array(),
                'lat'             => 50.9073,
                'lng'             => -1.1372,
                '_branchId'       => '',
            ),
        );
    }
}
