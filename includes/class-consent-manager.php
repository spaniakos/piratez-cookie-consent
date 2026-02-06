<?php
/**
 * Consent Manager — GCM v2: default in head before any tag, runtime only update.
 *
 * @package Piratez_Cookie_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Piratez_CC_Consent_Manager {

	private static $instance = null;
	const STORAGE_KEY = 'pcc_consent';

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->init_hooks();
	}

	private function init_hooks() {
		add_action( 'wp_head', array( $this, 'output_consent_default' ), 0 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_runtime' ) );
	}

	public function output_consent_default() {
		if ( is_admin() ) {
			return;
		}
		?>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('consent', 'default', {
  'analytics_storage': 'denied',
  'ad_storage': 'denied',
  'ad_user_data': 'denied',
  'ad_personalization': 'denied',
  'wait_for_update': 500
});
</script>
		<?php
	}

	public function enqueue_runtime() {
		if ( is_admin() ) {
			return;
		}
		wp_enqueue_script(
			'pcc-consent-runtime',
			PIRATEZ_CC_PLUGIN_URL . 'assets/js/consent-runtime.js',
			array(),
			PIRATEZ_CC_VERSION,
			true
		);
		wp_localize_script(
			'pcc-consent-runtime',
			'pccConfig',
			array(
				'storageKey' => self::STORAGE_KEY,
				'debug'      => defined( 'WP_DEBUG' ) && WP_DEBUG,
			)
		);
	}

	public static function get_default_state() {
		return array(
			'analytics' => false,
			'ads'       => false,
		);
	}
}
