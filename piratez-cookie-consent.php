<?php
/**
 * Plugin Name: Piratez Cookie Consent
 * Plugin URI: https://github.com/spaniakos/piratez-cookie-consent
 * Description: Google Consent Mode v2 cookie consent. Default denied in head, runtime update, floating popup with re-open control.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Spaniakos
 * License: GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: piratez-cookie-consent
 * Domain Path: /languages
 *
 * Based on MaxtDesign Cookie Consent – Google Consent Mode v2 by MaxtDesign.
 * Original plugin: https://wordpress.com/plugins/maxtdesign-cookie-consent
 * Original author: https://maxtdesign.com
 * This derivative is licensed under the same GPL v3.
 *
 * @package Piratez_Cookie_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PIRATEZ_CC_VERSION', '1.0.0' );
define( 'PIRATEZ_CC_PLUGIN_FILE', __FILE__ );
define( 'PIRATEZ_CC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PIRATEZ_CC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PIRATEZ_CC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

function piratez_cc_default_settings() {
	return array(
		'popup_enabled'        => true,
		'popup_style'          => 'minimal',
		'popup_position'       => 'float',
		'popup_primary_color'  => '#0073aa',
		'popup_animation'      => 'slide',
		'popup_title'          => __( 'Cookie Consent', 'piratez-cookie-consent' ),
		'popup_message'        => __( 'We use cookies to enhance your browsing experience and analyze our traffic.', 'piratez-cookie-consent' ),
		'popup_shown_duration' => 7,
		'reprompt_on_decline'  => false,
		'elementor_popup_id'   => '',
	);
}

add_action( 'plugins_loaded', 'piratez_cc_init_plugin' );
function piratez_cc_init_plugin() {
	require_once PIRATEZ_CC_PLUGIN_DIR . 'includes/class-consent-manager.php';
	require_once PIRATEZ_CC_PLUGIN_DIR . 'includes/class-popup-system.php';

	Piratez_CC_Consent_Manager::get_instance();
	Piratez_CC_Popup_System::get_instance();

	if ( is_admin() ) {
		add_action( 'init', 'piratez_cc_load_admin', 5 );
	}
}

function piratez_cc_load_admin() {
	require_once PIRATEZ_CC_PLUGIN_DIR . 'admin/class-admin.php';
	Piratez_CC_Admin::get_instance();
}

register_activation_hook( __FILE__, 'piratez_cc_activate' );
function piratez_cc_activate() {
	if ( ! get_option( 'piratez_cc_settings' ) ) {
		add_option( 'piratez_cc_settings', piratez_cc_default_settings() );
	}
	update_option( 'piratez_cc_version', PIRATEZ_CC_VERSION );
}
