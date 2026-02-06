<?php
/**
 * Admin: Tools menu, settings page. No shortcodes.
 *
 * @package Piratez_Cookie_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Piratez_CC_Admin {

	private static $instance = null;
	const OPTION_NAME = 'piratez_cc_settings';
	const OPTION_GROUP = 'piratez_cc_settings_group';
	const PAGE_SLUG    = 'piratez-cookie-consent';

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
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_filter( 'plugin_action_links_' . PIRATEZ_CC_PLUGIN_BASENAME, array( $this, 'add_settings_link' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	public function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => piratez_cc_default_settings(),
			)
		);

		add_settings_section( 'pcc_section_appearance', __( 'Popup Appearance', 'piratez-cookie-consent' ), array( $this, 'render_section_appearance' ), self::PAGE_SLUG );
		add_settings_field( 'popup_enabled', __( 'Enable Popup', 'piratez-cookie-consent' ), array( $this, 'render_field_popup_enabled' ), self::PAGE_SLUG, 'pcc_section_appearance' );
		add_settings_field( 'popup_style', __( 'Style Preset', 'piratez-cookie-consent' ), array( $this, 'render_field_popup_style' ), self::PAGE_SLUG, 'pcc_section_appearance' );
		add_settings_field( 'popup_position', __( 'Position', 'piratez-cookie-consent' ), array( $this, 'render_field_popup_position' ), self::PAGE_SLUG, 'pcc_section_appearance' );
		add_settings_field( 'popup_primary_color', __( 'Primary Color', 'piratez-cookie-consent' ), array( $this, 'render_field_popup_primary_color' ), self::PAGE_SLUG, 'pcc_section_appearance' );
		add_settings_field( 'popup_animation', __( 'Animation', 'piratez-cookie-consent' ), array( $this, 'render_field_popup_animation' ), self::PAGE_SLUG, 'pcc_section_appearance' );

		add_settings_section( 'pcc_section_content', __( 'Popup Content', 'piratez-cookie-consent' ), array( $this, 'render_section_content' ), self::PAGE_SLUG );
		add_settings_field( 'popup_title', __( 'Popup Title', 'piratez-cookie-consent' ), array( $this, 'render_field_popup_title' ), self::PAGE_SLUG, 'pcc_section_content' );
		add_settings_field( 'popup_message', __( 'Popup Message', 'piratez-cookie-consent' ), array( $this, 'render_field_popup_message' ), self::PAGE_SLUG, 'pcc_section_content' );

		add_settings_section( 'pcc_section_behavior', __( 'Behavior', 'piratez-cookie-consent' ), array( $this, 'render_section_behavior' ), self::PAGE_SLUG );
		add_settings_field( 'popup_shown_duration', __( 'Cookie Duration', 'piratez-cookie-consent' ), array( $this, 'render_field_popup_shown_duration' ), self::PAGE_SLUG, 'pcc_section_behavior' );
		add_settings_field( 'reprompt_on_decline', __( 'Re-prompt on Decline', 'piratez-cookie-consent' ), array( $this, 'render_field_reprompt_on_decline' ), self::PAGE_SLUG, 'pcc_section_behavior' );

		add_settings_section( 'pcc_section_elementor', __( 'Elementor (Optional)', 'piratez-cookie-consent' ), array( $this, 'render_section_elementor' ), self::PAGE_SLUG );
		add_settings_field( 'elementor_popup_id', __( 'Elementor Popup ID', 'piratez-cookie-consent' ), array( $this, 'render_field_elementor_popup_id' ), self::PAGE_SLUG, 'pcc_section_elementor' );
	}

	public function sanitize_settings( $input ) {
		$defaults = piratez_cc_default_settings();
		$out      = array();
		$out['popup_enabled'] = ! empty( $input['popup_enabled'] );
		$allowed_styles = array( 'minimal', 'modern', 'bold' );
		$out['popup_style'] = ( isset( $input['popup_style'] ) && in_array( $input['popup_style'], $allowed_styles, true ) ) ? $input['popup_style'] : $defaults['popup_style'];
		$allowed_positions = array( 'float', 'top', 'bottom', 'center' );
		$out['popup_position'] = ( isset( $input['popup_position'] ) && in_array( $input['popup_position'], $allowed_positions, true ) ) ? $input['popup_position'] : $defaults['popup_position'];
		$out['popup_primary_color'] = ! empty( $input['popup_primary_color'] ) ? sanitize_hex_color( $input['popup_primary_color'] ) : $defaults['popup_primary_color'];
		$allowed_animations = array( 'slide', 'fade', 'none' );
		$out['popup_animation'] = ( isset( $input['popup_animation'] ) && in_array( $input['popup_animation'], $allowed_animations, true ) ) ? $input['popup_animation'] : $defaults['popup_animation'];
		$out['popup_title']   = ! empty( $input['popup_title'] ) ? sanitize_text_field( $input['popup_title'] ) : $defaults['popup_title'];
		$out['popup_message'] = ! empty( $input['popup_message'] ) ? sanitize_textarea_field( $input['popup_message'] ) : $defaults['popup_message'];
		$dur = isset( $input['popup_shown_duration'] ) ? absint( $input['popup_shown_duration'] ) : 0;
		$out['popup_shown_duration'] = ( $dur >= 1 && $dur <= 365 ) ? $dur : $defaults['popup_shown_duration'];
		$out['reprompt_on_decline'] = ! empty( $input['reprompt_on_decline'] );
		$out['elementor_popup_id']  = ! empty( $input['elementor_popup_id'] ) ? absint( $input['elementor_popup_id'] ) : '';
		return $out;
	}

	public function add_menu() {
		add_management_page(
			__( 'Piratez Cookie Consent', 'piratez-cookie-consent' ),
			__( 'Piratez Cookie Consent', 'piratez-cookie-consent' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_settings_page' )
		);
	}

	public function add_settings_link( $links ) {
		$links[] = '<a href="' . esc_url( admin_url( 'tools.php?page=' . self::PAGE_SLUG ) ) . '">' . esc_html__( 'Settings', 'piratez-cookie-consent' ) . '</a>';
		return $links;
	}

	public function enqueue_admin_assets( $hook ) {
		if ( 'tools_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'pcc-admin', PIRATEZ_CC_PLUGIN_URL . 'admin/css/admin.css', array(), PIRATEZ_CC_VERSION );
		wp_enqueue_script( 'pcc-admin', PIRATEZ_CC_PLUGIN_URL . 'admin/js/admin.js', array( 'jquery', 'wp-color-picker' ), PIRATEZ_CC_VERSION, true );
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p class="description"><?php esc_html_e( 'Configure consent popup and the bottom-left "Cookie settings" button. GCM v2: default denied in head, runtime update. Float = unanchored card.', 'piratez-cookie-consent' ); ?></p>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button( __( 'Save Settings', 'piratez-cookie-consent' ) );
				?>
			</form>
		</div>
		<?php
	}

	public function render_section_appearance() { echo '<p>' . esc_html__( 'Customize the consent popup appearance.', 'piratez-cookie-consent' ) . '</p>'; }
	public function render_section_content() { echo '<p>' . esc_html__( 'Text shown in the popup.', 'piratez-cookie-consent' ) . '</p>'; }
	public function render_section_behavior() { echo '<p>' . esc_html__( 'When to show again and re-prompt options.', 'piratez-cookie-consent' ) . '</p>'; }
	public function render_section_elementor() { echo '<p>' . esc_html__( 'Use an Elementor popup instead of the built-in one. Leave blank for built-in popup and bottom-left Cookie settings button.', 'piratez-cookie-consent' ) . '</p>'; }

	public function render_field_popup_enabled() {
		$s = get_option( self::OPTION_NAME, piratez_cc_default_settings() );
		?><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[popup_enabled]" value="1" <?php checked( ! empty( $s['popup_enabled'] ), true ); ?> /> <?php esc_html_e( 'Display the consent popup', 'piratez-cookie-consent' ); ?></label><?php
	}

	public function render_field_popup_style() {
		$s = get_option( self::OPTION_NAME, piratez_cc_default_settings() );
		$v = isset( $s['popup_style'] ) ? $s['popup_style'] : 'minimal';
		?><select name="<?php echo esc_attr( self::OPTION_NAME ); ?>[popup_style]">
			<option value="minimal" <?php selected( $v, 'minimal' ); ?>><?php esc_html_e( 'Minimal', 'piratez-cookie-consent' ); ?></option>
			<option value="modern" <?php selected( $v, 'modern' ); ?>><?php esc_html_e( 'Modern', 'piratez-cookie-consent' ); ?></option>
			<option value="bold" <?php selected( $v, 'bold' ); ?>><?php esc_html_e( 'Bold', 'piratez-cookie-consent' ); ?></option>
		</select><?php
	}

	public function render_field_popup_position() {
		$s = get_option( self::OPTION_NAME, piratez_cc_default_settings() );
		$v = isset( $s['popup_position'] ) ? $s['popup_position'] : 'float';
		?><select name="<?php echo esc_attr( self::OPTION_NAME ); ?>[popup_position]">
			<option value="float" <?php selected( $v, 'float' ); ?>><?php esc_html_e( 'Float (bottom-right)', 'piratez-cookie-consent' ); ?></option>
			<option value="top" <?php selected( $v, 'top' ); ?>><?php esc_html_e( 'Top Banner', 'piratez-cookie-consent' ); ?></option>
			<option value="bottom" <?php selected( $v, 'bottom' ); ?>><?php esc_html_e( 'Bottom Banner', 'piratez-cookie-consent' ); ?></option>
			<option value="center" <?php selected( $v, 'center' ); ?>><?php esc_html_e( 'Center Modal', 'piratez-cookie-consent' ); ?></option>
		</select>
		<p class="description"><?php esc_html_e( 'Float is an unanchored card. The "Cookie settings" button stays bottom-left.', 'piratez-cookie-consent' ); ?></p><?php
	}

	public function render_field_popup_primary_color() {
		$s = get_option( self::OPTION_NAME, piratez_cc_default_settings() );
		$v = isset( $s['popup_primary_color'] ) ? $s['popup_primary_color'] : '#0073aa';
		?><input type="text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[popup_primary_color]" value="<?php echo esc_attr( $v ); ?>" class="pcc-color-picker" data-default-color="#0073aa" /> <?php
	}

	public function render_field_popup_animation() {
		$s = get_option( self::OPTION_NAME, piratez_cc_default_settings() );
		$v = isset( $s['popup_animation'] ) ? $s['popup_animation'] : 'slide';
		?><select name="<?php echo esc_attr( self::OPTION_NAME ); ?>[popup_animation]">
			<option value="slide" <?php selected( $v, 'slide' ); ?>><?php esc_html_e( 'Slide', 'piratez-cookie-consent' ); ?></option>
			<option value="fade" <?php selected( $v, 'fade' ); ?>><?php esc_html_e( 'Fade', 'piratez-cookie-consent' ); ?></option>
			<option value="none" <?php selected( $v, 'none' ); ?>><?php esc_html_e( 'None', 'piratez-cookie-consent' ); ?></option>
		</select><?php
	}

	public function render_field_popup_title() {
		$s = get_option( self::OPTION_NAME, piratez_cc_default_settings() );
		$v = isset( $s['popup_title'] ) ? $s['popup_title'] : '';
		?><input type="text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[popup_title]" value="<?php echo esc_attr( $v ); ?>" class="regular-text" /> <?php
	}

	public function render_field_popup_message() {
		$s = get_option( self::OPTION_NAME, piratez_cc_default_settings() );
		$v = isset( $s['popup_message'] ) ? $s['popup_message'] : '';
		?><textarea name="<?php echo esc_attr( self::OPTION_NAME ); ?>[popup_message]" rows="3" class="large-text"><?php echo esc_textarea( $v ); ?></textarea> <?php
	}

	public function render_field_popup_shown_duration() {
		$s = get_option( self::OPTION_NAME, piratez_cc_default_settings() );
		$v = isset( $s['popup_shown_duration'] ) ? $s['popup_shown_duration'] : 7;
		?><input type="number" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[popup_shown_duration]" value="<?php echo esc_attr( $v ); ?>" min="1" max="365" class="small-text" /> <?php esc_html_e( 'days', 'piratez-cookie-consent' ); ?>
		<p class="description"><?php esc_html_e( 'Days before showing the popup again after the user closes it.', 'piratez-cookie-consent' ); ?></p><?php
	}

	public function render_field_reprompt_on_decline() {
		$s = get_option( self::OPTION_NAME, piratez_cc_default_settings() );
		?><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[reprompt_on_decline]" value="1" <?php checked( ! empty( $s['reprompt_on_decline'] ), true ); ?> /> <?php esc_html_e( 'Re-show popup once per session if user declines all', 'piratez-cookie-consent' ); ?></label><?php
	}

	public function render_field_elementor_popup_id() {
		$s = get_option( self::OPTION_NAME, piratez_cc_default_settings() );
		$v = isset( $s['elementor_popup_id'] ) ? $s['elementor_popup_id'] : '';
		?><input type="number" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[elementor_popup_id]" value="<?php echo esc_attr( $v ); ?>" min="1" step="1" class="small-text" placeholder="<?php esc_attr_e( 'Leave blank for built-in', 'piratez-cookie-consent' ); ?>" /> <?php
	}
}
