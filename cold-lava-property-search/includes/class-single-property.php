<?php
/**
 * Single Property Page Shortcode
 *
 * Renders the [cl_single_property] shortcode - a full-page property detail view
 * with gallery, key features, description, info cards, map, agent contact card,
 * and similar properties section.
 *
 * @package ColdLavaPropertySearch
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CLPS_Single_Property {

    /**
     * Constructor.
     */
    public function __construct() {
        add_shortcode( 'cl_single_property', array( $this, 'render_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Enqueue CSS/JS only on pages with the shortcode.
     */
    public function enqueue_assets() {
        global $post;

        if ( ! is_a( $post, 'WP_Post' ) ) {
            return;
        }

        $has_shortcode = has_shortcode( $post->post_content, 'cl_single_property' );

        if ( ! $has_shortcode ) {
            $elementor_data = get_post_meta( $post->ID, '_elementor_data', true );
            if ( empty( $elementor_data ) || strpos( $elementor_data, 'cl_single_property' ) === false ) {
                return;
            }
        }

        // Google Fonts
        wp_enqueue_style(
            'clps-google-fonts-sp',
            'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap',
            array(),
            CLPS_VERSION
        );

        // Plugin CSS (shared with property search)
        wp_enqueue_style(
            'clps-property-search',
            CLPS_PLUGIN_URL . 'assets/css/property-search.css',
            array(),
            CLPS_VERSION
        );

        // Single property CSS
        wp_enqueue_style(
            'clps-single-property',
            CLPS_PLUGIN_URL . 'assets/css/single-property.css',
            array( 'clps-property-search' ),
            CLPS_VERSION
        );

        // Single property JS
        wp_enqueue_script(
            'clps-single-property',
            CLPS_PLUGIN_URL . 'assets/js/single-property.js',
            array( 'jquery' ),
            CLPS_VERSION,
            true
        );

        // Pass data to JS
        wp_localize_script( 'clps-single-property', 'clpsSingleData', array(
            'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'clps_frontend_nonce' ),
            'pluginUrl'   => CLPS_PLUGIN_URL,
            'agentName'   => get_option( 'clps_agent_name', 'Bernards Estate Agents' ),
            'agentPhone'  => get_option( 'clps_agent_phone', '023 9200 8575' ),
            'agentEmail'  => get_option( 'clps_agent_email', '' ),
            'searchUrl'   => home_url( '/property-search/' ),
        ) );
    }

    /**
     * Render the [cl_single_property] shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( array(), $atts, 'cl_single_property' );

        ob_start();
        $this->render_single_property_page();
        return ob_get_clean();
    }

    /**
     * Render the full single property page HTML.
     */
    private function render_single_property_page() {
        ?>
        <div id="clps-single-property" class="clps-sp-wrapper">

            <!-- Back Bar -->
            <div class="sp-back-bar">
                <div class="sp-back-inner">
                    <a class="sp-back-link" id="clps-sp-back-link" href="#">
                        <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
                        Back to search results
                    </a>
                </div>
            </div>

            <!-- Image Gallery -->
            <div class="sp-gallery">
                <div class="sp-gallery-grid" id="clps-sp-gallery">
                    <div class="rm-loading">
                        <div class="rm-loading-spinner"></div>
                        <p><?php echo esc_html( 'Loading property...' ); ?></p>
                    </div>
                </div>
            </div>

            <!-- Header Section -->
            <div class="sp-header-section" id="clps-sp-header"></div>

            <!-- Info Strip -->
            <div class="sp-info-strip">
                <div class="sp-info-strip-inner" id="clps-sp-info-strip"></div>
            </div>

            <!-- Content -->
            <div class="sp-content">
                <div class="sp-main" id="clps-sp-main"></div>
                <div class="sp-sidebar">
                    <div class="sp-agent-card" id="clps-sp-agent"></div>
                </div>
            </div>

            <!-- Info Cards Row -->
            <div style="max-width:1200px;margin:0 auto;padding:0 20px;">
                <div class="sp-info-cards" id="clps-sp-info-cards"></div>
            </div>

            <!-- Map Section -->
            <div class="sp-content" style="padding-bottom:20px;">
                <div class="sp-main">
                    <div class="sp-map-section" id="clps-sp-map"></div>
                </div>
                <div class="sp-sidebar"></div>
            </div>

            <!-- Similar Properties -->
            <div style="background:#f4f5f7;padding:40px 0;">
                <div class="sp-similar">
                    <h2 class="sp-similar-title">Similar Properties Nearby</h2>
                    <div class="sp-similar-grid" id="clps-sp-similar"></div>
                </div>
            </div>

        </div>
        <?php
    }
}
