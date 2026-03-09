<?php
/**
 * Contact Page Shortcode
 *
 * Renders the [cl_contact_page] shortcode with office cards,
 * interactive Leaflet map, and contact details for all Bernards branches.
 *
 * @package ColdLavaPropertySearch
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CLPS_Contact_Page {

    /**
     * Constructor.
     */
    public function __construct() {
        add_shortcode( 'cl_contact_page', array( $this, 'render_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Enqueue Leaflet CSS/JS only on pages with the shortcode.
     */
    public function enqueue_assets() {
        global $post;

        if ( ! is_a( $post, 'WP_Post' ) ) {
            return;
        }

        $has_shortcode = has_shortcode( $post->post_content, 'cl_contact_page' );

        if ( ! $has_shortcode ) {
            $elementor_data = get_post_meta( $post->ID, '_elementor_data', true );
            if ( empty( $elementor_data ) || strpos( $elementor_data, 'cl_contact_page' ) === false ) {
                return;
            }
        }

        // Leaflet CSS
        wp_enqueue_style(
            'clps-leaflet-css',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            array(),
            '1.9.4'
        );

        // Leaflet JS
        wp_enqueue_script(
            'clps-leaflet-js',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            array(),
            '1.9.4',
            true
        );
    }

    /**
     * Render the [cl_contact_page] shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( array(), $atts, 'cl_contact_page' );

        ob_start();
        $this->render_contact_page();
        return ob_get_clean();
    }

    /**
     * Get the list of office branches.
     *
     * @return array Array of office data.
     */
    private function get_offices() {
        return array(
            array(
                'name'    => 'Bernards Admiralty Quarter',
                'lat'     => 50.7967,
                'lng'     => -1.1081,
                'phone'   => '023 9200 8575',
                'email'   => 'southsea@bernardsea.co.uk',
                'address' => '119 Queen Street, The Hard, Portsmouth PO1 3HY',
            ),
            array(
                'name'    => 'Bernards Southsea',
                'lat'     => 50.7814,
                'lng'     => -1.0870,
                'phone'   => '023 9286 4974',
                'email'   => 'southsea@bernardsea.co.uk',
                'address' => '8 Clarendon Road, Southsea PO5 2EE',
            ),
            array(
                'name'    => 'Bernards Drayton',
                'lat'     => 50.8588,
                'lng'     => -1.0640,
                'phone'   => '02392 728091',
                'email'   => 'drayton@bernardsEA.co.uk',
                'address' => 'Lower Drayton Ln, Drayton, Portsmouth PO6 2HA',
            ),
            array(
                'name'    => 'Bernards Fareham',
                'lat'     => 50.8515,
                'lng'     => -1.1785,
                'phone'   => '01329 756500',
                'email'   => 'fareham@bernardsEA.co.uk',
                'address' => '79 High St, Fareham PO16 7AX',
            ),
            array(
                'name'    => 'Bernards Waterlooville',
                'lat'     => 50.8808,
                'lng'     => -1.0304,
                'phone'   => '023 9223 2888',
                'email'   => 'waterlooville@bernardsea.co.uk',
                'address' => '47 London Road, Waterlooville PO7 7EX',
            ),
            array(
                'name'    => 'Bernards Gosport',
                'lat'     => 50.7939,
                'lng'     => -1.1240,
                'phone'   => '02392 0046601',
                'email'   => 'gosport@bernardsEA.co.uk',
                'address' => '97 High St, Gosport PO12 1DS',
            ),
            array(
                'name'    => 'Bernards Lee-On-The-Solent',
                'lat'     => 50.8013,
                'lng'     => -1.2024,
                'phone'   => '02392 553636',
                'email'   => 'leeonsolent@bernardsea.co.uk',
                'address' => '118-120 High St, Lee-on-the-Solent PO13 9DB',
            ),
            array(
                'name'    => 'Bernards Havant',
                'lat'     => 50.8529,
                'lng'     => -0.9836,
                'phone'   => '02392 482 147',
                'email'   => 'havant@bernardsea.co.uk',
                'address' => '1 North Street Arcade, Havant PO9 1PX',
            ),
        );
    }

    /**
     * Render the full contact page HTML.
     */
    private function render_contact_page() {
        $offices = $this->get_offices();
        $offices_json = wp_json_encode( $offices );
        ?>
        <style>
            .clps-contact-wrapper { font-family: "Montserrat", sans-serif; }

            /* Map */
            .clps-contact-map {
                width: 100%;
                height: 450px;
                z-index: 1;
                border-radius: 4px;
                margin-bottom: 40px;
            }
            .clps-contact-map .leaflet-tile-pane {
                filter: grayscale(100%) contrast(1.1);
            }
            .clps-contact-map .leaflet-marker-pane { filter: none; }
            .clps-contact-map .leaflet-control-attribution { display: none; }
            .clps-marker-icon { background: none; border: none; }
            .clps-contact-map .leaflet-popup-content h4 {
                margin: 0 0 6px 0;
                color: #5D3384;
                font-family: "Roboto Slab", serif;
                font-size: 15px;
            }
            .clps-contact-map .leaflet-popup-content p {
                margin: 0;
                font-size: 13px;
                line-height: 1.5;
                color: #333;
            }
            .clps-contact-map .leaflet-popup-content a {
                color: #5D3384;
                text-decoration: none;
            }
            .clps-contact-map .leaflet-popup-content a:hover {
                text-decoration: underline;
            }

            /* Heading */
            .clps-contact-heading {
                text-align: center;
                padding: 40px 20px 30px;
            }
            .clps-contact-heading h2 {
                font-family: "Roboto Slab", serif;
                font-size: 28px;
                font-weight: 600;
                color: #5D3384;
                margin: 0 0 12px;
            }
            .clps-contact-heading p {
                font-size: 15px;
                color: #666;
                max-width: 700px;
                margin: 0 auto;
                line-height: 1.6;
            }

            /* Office cards grid */
            .clps-office-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 24px;
                max-width: 1200px;
                margin: 0 auto 40px;
                padding: 0 20px;
            }
            .clps-office-card {
                background: #fff;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 24px;
                text-align: center;
                transition: box-shadow 0.2s ease, transform 0.2s ease;
                box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            }
            .clps-office-card:hover {
                box-shadow: 0 4px 16px rgba(0,0,0,0.12);
                transform: translateY(-2px);
            }
            .clps-office-card h3 {
                font-family: "Roboto Slab", serif;
                font-size: 16px;
                font-weight: 600;
                color: #5D3384;
                margin: 0 0 12px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .clps-office-card p {
                font-size: 13px;
                color: #555;
                line-height: 1.6;
                margin: 0;
            }
            .clps-office-card a {
                color: #5D3384;
                text-decoration: none;
            }
            .clps-office-card a:hover {
                text-decoration: underline;
            }

            @media (max-width: 900px) {
                .clps-office-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }
            @media (max-width: 600px) {
                .clps-office-grid {
                    grid-template-columns: 1fr;
                }
                .clps-contact-map {
                    height: 300px;
                }
            }
        </style>

        <div class="clps-contact-wrapper">

            <!-- Heading -->
            <div class="clps-contact-heading">
                <h2>Bernards Areas We Love</h2>
                <p>We know and truly care about the cities, towns, villages, and neighbourhoods we work within. Start your move today - visit or contact us to get started. We'd love to hear from you!</p>
            </div>

            <!-- Map -->
            <div id="clps-contact-map" class="clps-contact-map"></div>

            <!-- Office Cards -->
            <div class="clps-office-grid">
                <?php foreach ( $offices as $office ) : ?>
                <div class="clps-office-card">
                    <h3><?php echo esc_html( str_replace( 'Bernards ', '', $office['name'] ) ); ?></h3>
                    <p>
                        Telephone: <a href="tel:<?php echo esc_attr( preg_replace( '/\s/', '', $office['phone'] ) ); ?>"><?php echo esc_html( $office['phone'] ); ?></a><br>
                        Email: <a href="mailto:<?php echo esc_attr( $office['email'] ); ?>"><?php echo esc_html( $office['email'] ); ?></a><br>
                        <?php echo esc_html( $office['address'] ); ?>
                    </p>
                </div>
                <?php endforeach; ?>
            </div>

        </div>

        <script>
        (function() {
            if (typeof L === 'undefined') return;

            var map = L.map('clps-contact-map', { scrollWheelZoom: false }).setView([50.8300, -1.0900], 11);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map);

            var purpleIcon = L.divIcon({
                className: 'clps-marker-icon',
                html: '<svg width="28" height="40" viewBox="0 0 28 40"><path d="M14 0C6.27 0 0 6.27 0 14c0 10.5 14 26 14 26s14-15.5 14-26C28 6.27 21.73 0 14 0z" fill="#5D3384"/><circle cx="14" cy="14" r="6" fill="white"/></svg>',
                iconSize: [28, 40],
                iconAnchor: [14, 40],
                popupAnchor: [0, -36]
            });

            var offices = <?php echo $offices_json; ?>;

            offices.forEach(function(o) {
                var marker = L.marker([o.lat, o.lng], { icon: purpleIcon }).addTo(map);
                marker.bindPopup(
                    '<h4>' + o.name + '</h4>' +
                    '<p>\ud83d\udcde <a href="tel:' + o.phone.replace(/\s/g, '') + '">' + o.phone + '</a><br>' +
                    '\u2709\ufe0f <a href="mailto:' + o.email + '">' + o.email + '</a><br>' +
                    '\ud83d\udccd ' + o.address + '</p>'
                );
                marker.on('mouseover', function() { this.openPopup(); });
                marker.on('mouseout', function() { this.closePopup(); });
            });
        })();
        </script>
        <?php
    }
}
