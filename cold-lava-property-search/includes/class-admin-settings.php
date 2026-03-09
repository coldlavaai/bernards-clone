<?php
/**
 * Admin Settings Page
 *
 * Registers the settings page under Settings > Property Search.
 * Handles Vebra API credentials and plugin configuration.
 *
 * @package ColdLavaPropertySearch
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CLPS_Admin_Settings {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'wp_ajax_clps_test_connection', array( $this, 'ajax_test_connection' ) );
        add_action( 'wp_ajax_clps_clear_cache', array( $this, 'ajax_clear_cache' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }

    /**
     * Add the settings page to the admin menu.
     */
    public function add_settings_page() {
        add_options_page(
            __( 'Property Search Settings', 'cl-property-search' ),
            __( 'Property Search', 'cl-property-search' ),
            'manage_options',
            'cl-property-search',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register settings with the Settings API.
     */
    public function register_settings() {
        // Vebra API section
        add_settings_section(
            'clps_vebra_section',
            __( 'Vebra Alto API Settings', 'cl-property-search' ),
            array( $this, 'render_vebra_section' ),
            'cl-property-search'
        );

        // Data Feed ID
        register_setting( 'clps_settings_group', 'clps_vebra_feed_id', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );
        add_settings_field(
            'clps_vebra_feed_id',
            __( 'Data Feed ID', 'cl-property-search' ),
            array( $this, 'render_text_field' ),
            'cl-property-search',
            'clps_vebra_section',
            array(
                'id'          => 'clps_vebra_feed_id',
                'description' => 'Your Vebra data feed ID (e.g. "12345"). Provided by Vebra.',
            )
        );

        // Username
        register_setting( 'clps_settings_group', 'clps_vebra_username', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );
        add_settings_field(
            'clps_vebra_username',
            __( 'Username', 'cl-property-search' ),
            array( $this, 'render_text_field' ),
            'cl-property-search',
            'clps_vebra_section',
            array(
                'id'          => 'clps_vebra_username',
                'description' => 'Your Vebra API username.',
            )
        );

        // Password
        register_setting( 'clps_settings_group', 'clps_vebra_password', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );
        add_settings_field(
            'clps_vebra_password',
            __( 'Password', 'cl-property-search' ),
            array( $this, 'render_password_field' ),
            'cl-property-search',
            'clps_vebra_section',
            array(
                'id'          => 'clps_vebra_password',
                'description' => 'Your Vebra API password.',
            )
        );

        // Display settings section
        add_settings_section(
            'clps_display_section',
            __( 'Display Settings', 'cl-property-search' ),
            array( $this, 'render_display_section' ),
            'cl-property-search'
        );

        // Show placeholders toggle
        register_setting( 'clps_settings_group', 'clps_show_placeholders', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '1',
        ) );
        add_settings_field(
            'clps_show_placeholders',
            __( 'Show Placeholder Properties', 'cl-property-search' ),
            array( $this, 'render_checkbox_field' ),
            'cl-property-search',
            'clps_display_section',
            array(
                'id'          => 'clps_show_placeholders',
                'description' => 'Show sample properties when Vebra API is not configured or returns no results.',
            )
        );

        // Agent settings section
        add_settings_section(
            'clps_agent_section',
            __( 'Agent Details', 'cl-property-search' ),
            array( $this, 'render_agent_section' ),
            'cl-property-search'
        );

        // Agent name
        register_setting( 'clps_settings_group', 'clps_agent_name', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );
        add_settings_field(
            'clps_agent_name',
            __( 'Agent Name', 'cl-property-search' ),
            array( $this, 'render_text_field' ),
            'cl-property-search',
            'clps_agent_section',
            array(
                'id'          => 'clps_agent_name',
                'description' => 'Estate agent name shown on property cards (e.g. "Bernards Estate Agents, Southsea").',
                'placeholder' => 'e.g. Bernards Estate Agents, Southsea',
            )
        );

        // Agent phone
        register_setting( 'clps_settings_group', 'clps_agent_phone', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );
        add_settings_field(
            'clps_agent_phone',
            __( 'Agent Phone', 'cl-property-search' ),
            array( $this, 'render_text_field' ),
            'cl-property-search',
            'clps_agent_section',
            array(
                'id'          => 'clps_agent_phone',
                'description' => 'Phone number shown on property cards.',
                'placeholder' => 'e.g. 023 9200 8575',
            )
        );

        // Agent email
        register_setting( 'clps_settings_group', 'clps_agent_email', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_email',
            'default'           => '',
        ) );
        add_settings_field(
            'clps_agent_email',
            __( 'Agent Email', 'cl-property-search' ),
            array( $this, 'render_text_field' ),
            'cl-property-search',
            'clps_agent_section',
            array(
                'id'          => 'clps_agent_email',
                'description' => 'Email address for enquiries.',
                'placeholder' => 'e.g. info@example.co.uk',
            )
        );
    }

    /**
     * Enqueue admin scripts on the settings page only.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( 'settings_page_cl-property-search' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'clps-admin',
            CLPS_PLUGIN_URL . 'assets/css/property-search.css',
            array(),
            CLPS_VERSION
        );

        // Inline admin JS
        wp_enqueue_script( 'jquery' );
        wp_add_inline_script( 'jquery', $this->get_admin_js() );
    }

    // ────────────────────────────────────────────
    // Section descriptions
    // ────────────────────────────────────────────

    /**
     * Render the Vebra API section description.
     */
    public function render_vebra_section() {
        echo '<p>' . esc_html__( 'Enter your Vebra Alto API credentials below. Contact Vebra support to obtain these.', 'cl-property-search' ) . '</p>';
    }

    /**
     * Render the display section description.
     */
    public function render_display_section() {
        echo '<p>' . esc_html__( 'Configure how the property search looks on your site.', 'cl-property-search' ) . '</p>';
    }

    /**
     * Render the agent section description.
     */
    public function render_agent_section() {
        echo '<p>' . esc_html__( 'Details shown on property listing cards and detail pages.', 'cl-property-search' ) . '</p>';
    }

    // ────────────────────────────────────────────
    // Field renderers
    // ────────────────────────────────────────────

    /**
     * Render a text input field.
     *
     * @param array $args Field arguments.
     */
    public function render_text_field( $args ) {
        $id          = $args['id'];
        $value       = get_option( $id, '' );
        $description = isset( $args['description'] ) ? $args['description'] : '';
        $placeholder = isset( $args['placeholder'] ) ? $args['placeholder'] : '';

        printf(
            '<input type="text" id="%s" name="%s" value="%s" class="regular-text" placeholder="%s" />',
            esc_attr( $id ),
            esc_attr( $id ),
            esc_attr( $value ),
            esc_attr( $placeholder )
        );

        if ( $description ) {
            printf( '<p class="description">%s</p>', esc_html( $description ) );
        }
    }

    /**
     * Render a password input field.
     *
     * @param array $args Field arguments.
     */
    public function render_password_field( $args ) {
        $id          = $args['id'];
        $value       = get_option( $id, '' );
        $description = isset( $args['description'] ) ? $args['description'] : '';

        printf(
            '<input type="password" id="%s" name="%s" value="%s" class="regular-text" />',
            esc_attr( $id ),
            esc_attr( $id ),
            esc_attr( $value )
        );

        if ( $description ) {
            printf( '<p class="description">%s</p>', esc_html( $description ) );
        }
    }

    /**
     * Render a checkbox field.
     *
     * @param array $args Field arguments.
     */
    public function render_checkbox_field( $args ) {
        $id          = $args['id'];
        $value       = get_option( $id, '1' );
        $description = isset( $args['description'] ) ? $args['description'] : '';

        printf(
            '<label><input type="checkbox" id="%s" name="%s" value="1" %s /> %s</label>',
            esc_attr( $id ),
            esc_attr( $id ),
            checked( $value, '1', false ),
            esc_html( $description )
        );
    }

    // ────────────────────────────────────────────
    // Settings page
    // ────────────────────────────────────────────

    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <div style="background:#fff;border:1px solid #ccd0d4;border-left:4px solid #00DEB6;padding:12px 16px;margin:16px 0;">
                <p style="margin:0;">
                    <strong><?php esc_html_e( 'Shortcode:', 'cl-property-search' ); ?></strong>
                    <code>[cl_property_search]</code>
                    <?php esc_html_e( '- Paste this into any page or Elementor text widget to display the property search.', 'cl-property-search' ); ?>
                </p>
            </div>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'clps_settings_group' );
                do_settings_sections( 'cl-property-search' );
                submit_button();
                ?>
            </form>

            <hr />

            <h2><?php esc_html_e( 'Tools', 'cl-property-search' ); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Test Connection', 'cl-property-search' ); ?></th>
                    <td>
                        <button type="button" id="clps-test-connection" class="button button-secondary">
                            <?php esc_html_e( 'Test Vebra API Connection', 'cl-property-search' ); ?>
                        </button>
                        <span id="clps-test-result" style="margin-left:12px;"></span>
                        <p class="description"><?php esc_html_e( 'Save settings first, then test the connection.', 'cl-property-search' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Clear Cache', 'cl-property-search' ); ?></th>
                    <td>
                        <button type="button" id="clps-clear-cache" class="button button-secondary">
                            <?php esc_html_e( 'Clear Property Cache', 'cl-property-search' ); ?>
                        </button>
                        <span id="clps-cache-result" style="margin-left:12px;"></span>
                        <p class="description"><?php esc_html_e( 'Forces a fresh fetch from Vebra on the next page load. Properties are cached for 15 minutes.', 'cl-property-search' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    // ────────────────────────────────────────────
    // AJAX handlers
    // ────────────────────────────────────────────

    /**
     * Test the Vebra API connection via AJAX.
     */
    public function ajax_test_connection() {
        check_ajax_referer( 'clps_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
        }

        $api    = new CLPS_Vebra_API();
        $result = $api->test_connection();

        if ( $result['success'] ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }

    /**
     * Clear the property cache via AJAX.
     */
    public function ajax_clear_cache() {
        check_ajax_referer( 'clps_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
        }

        delete_transient( 'clps_vebra_properties' );
        delete_transient( 'clps_vebra_token' );

        wp_send_json_success( array( 'message' => 'Cache cleared successfully.' ) );
    }

    /**
     * Get admin JavaScript for test connection and clear cache buttons.
     *
     * @return string JavaScript code.
     */
    private function get_admin_js() {
        $nonce = wp_create_nonce( 'clps_admin_nonce' );

        return "
        jQuery(function($) {
            $('#clps-test-connection').on('click', function() {
                var btn = $(this);
                var result = $('#clps-test-result');
                btn.prop('disabled', true).text('Testing...');
                result.html('');

                $.post(ajaxurl, {
                    action: 'clps_test_connection',
                    nonce: '" . esc_js( $nonce ) . "'
                }).done(function(response) {
                    if (response.success) {
                        result.html('<span style=\"color:#00a32a;font-weight:600;\">' + response.data.message + '</span>');
                    } else {
                        result.html('<span style=\"color:#d63638;font-weight:600;\">' + response.data.message + '</span>');
                    }
                }).fail(function() {
                    result.html('<span style=\"color:#d63638;\">Request failed. Check your server connection.</span>');
                }).always(function() {
                    btn.prop('disabled', false).text('Test Vebra API Connection');
                });
            });

            $('#clps-clear-cache').on('click', function() {
                var btn = $(this);
                var result = $('#clps-cache-result');
                btn.prop('disabled', true).text('Clearing...');
                result.html('');

                $.post(ajaxurl, {
                    action: 'clps_clear_cache',
                    nonce: '" . esc_js( $nonce ) . "'
                }).done(function(response) {
                    if (response.success) {
                        result.html('<span style=\"color:#00a32a;font-weight:600;\">' + response.data.message + '</span>');
                    } else {
                        result.html('<span style=\"color:#d63638;font-weight:600;\">' + response.data.message + '</span>');
                    }
                }).fail(function() {
                    result.html('<span style=\"color:#d63638;\">Request failed.</span>');
                }).always(function() {
                    btn.prop('disabled', false).text('Clear Property Cache');
                });
            });
        });
        ";
    }
}
