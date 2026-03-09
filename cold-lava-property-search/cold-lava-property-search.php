<?php
/**
 * Plugin Name: Cold Lava Property Search
 * Plugin URI: https://coldlava.co.uk
 * Description: Property search with Vebra Alto API integration. Shortcodes: [cl_property_search] for property search, [cl_contact_page] for contact page, [cl_single_property] for property detail view.
 * Version: 1.1.0
 * Author: Cold Lava
 * Author URI: https://coldlava.co.uk
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cl-property-search
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CLPS_VERSION', '1.1.0' );
define( 'CLPS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CLPS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CLPS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Include classes
require_once CLPS_PLUGIN_DIR . 'includes/class-vebra-api.php';
require_once CLPS_PLUGIN_DIR . 'includes/class-admin-settings.php';
require_once CLPS_PLUGIN_DIR . 'includes/class-property-search.php';
require_once CLPS_PLUGIN_DIR . 'includes/class-contact-page.php';
require_once CLPS_PLUGIN_DIR . 'includes/class-single-property.php';

/**
 * Initialize the plugin.
 */
function clps_init() {
    // Admin settings
    if ( is_admin() ) {
        new CLPS_Admin_Settings();
    }

    // Frontend shortcodes and AJAX
    new CLPS_Property_Search();
    new CLPS_Contact_Page();
    new CLPS_Single_Property();
}
add_action( 'plugins_loaded', 'clps_init' );

/**
 * Activation hook - set default options.
 */
function clps_activate() {
    $defaults = array(
        'clps_vebra_feed_id'          => '',
        'clps_vebra_username'         => '',
        'clps_vebra_password'         => '',
        'clps_show_placeholders'      => '1',
        'clps_agent_name'             => '',
        'clps_agent_phone'            => '',
        'clps_agent_email'            => '',
    );

    foreach ( $defaults as $key => $value ) {
        if ( false === get_option( $key ) ) {
            add_option( $key, $value );
        }
    }

    // Flush rewrite rules for our custom query var
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'clps_activate' );

/**
 * Deactivation hook.
 */
function clps_deactivate() {
    // Clean up transients
    delete_transient( 'clps_vebra_properties' );
    delete_transient( 'clps_vebra_token' );
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'clps_deactivate' );

/**
 * Add settings link on the Plugins page.
 */
function clps_plugin_action_links( $links ) {
    $settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=cl-property-search' ) ) . '">' . esc_html__( 'Settings', 'cl-property-search' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . CLPS_PLUGIN_BASENAME, 'clps_plugin_action_links' );
