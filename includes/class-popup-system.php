<?php
/**
 * Popup System — Load when popup enabled; auto-show only when no cookie. Re-open via bottom-left pill.
 *
 * @package Piratez_Cookie_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Piratez_CC_Popup_System {

	private static $instance = null;
	const SHOWN_COOKIE = 'pcc_popup_shown';

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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_popup_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_settings_trigger' ), 5 );
		add_action( 'wp_footer', array( $this, 'render_popup' ) );
	}

	private function is_popup_enabled() {
		if ( is_admin() ) {
			return false;
		}
		$settings = get_option( 'piratez_cc_settings', piratez_cc_default_settings() );
		return ! empty( $settings['popup_enabled'] ) && empty( $settings['elementor_popup_id'] );
	}

	private function should_show_popup() {
		return $this->is_popup_enabled() && ! isset( $_COOKIE[ self::SHOWN_COOKIE ] );
	}

	public function enqueue_popup_assets() {
		if ( ! $this->is_popup_enabled() ) {
			return;
		}
		$settings      = get_option( 'piratez_cc_settings', piratez_cc_default_settings() );
		$primary       = ! empty( $settings['popup_primary_color'] ) ? sanitize_hex_color( $settings['popup_primary_color'] ) : '#0073aa';
		$primary_hover = $primary . 'dd';

		wp_enqueue_style(
			'pcc-popup',
			PIRATEZ_CC_PLUGIN_URL . 'assets/css/popup.css',
			array(),
			PIRATEZ_CC_VERSION,
			'all'
		);
		$custom_css = "
			.pcc-popup__button--primary { background-color: {$primary}; border-color: {$primary}; }
			.pcc-popup__button--primary:hover, .pcc-popup__button--primary:focus { background-color: {$primary_hover}; border-color: {$primary_hover}; }
		";
		wp_add_inline_style( 'pcc-popup', wp_strip_all_tags( $custom_css ) );

		wp_register_script( 'pcc-popup-behavior', false, array( 'pcc-consent-runtime' ), PIRATEZ_CC_VERSION, true );
		wp_enqueue_script( 'pcc-popup-behavior' );
		wp_localize_script(
			'pcc-popup-behavior',
			'pccPopupConfig',
			array(
				'cookieName'      => self::SHOWN_COOKIE,
				'cookieDuration'  => absint( $settings['popup_shown_duration'] ),
				'repromptDecline' => ! empty( $settings['reprompt_on_decline'] ),
				'position'        => isset( $settings['popup_position'] ) ? $settings['popup_position'] : 'float',
				'autoShow'        => $this->should_show_popup(),
			)
		);
		wp_add_inline_script( 'pcc-popup-behavior', $this->get_popup_js() );
	}

	private function get_popup_js() {
		return <<<'JS'
(function(w,d){
  'use strict';
  var c=w.pccPopupConfig||{}, popup=null, closeBtn=null, firstFocus=null, lastFocus=null;
  function hasShownCookie(){
    var name=c.cookieName||'pcc_popup_shown';
    var parts=d.cookie.split(';');
    for(var i=0;i<parts.length;i++){
      var p=parts[i].replace(/^\s+|\s+$/g,'');
      if(p.indexOf(name+'=')===0) return true;
    }
    return false;
  }
  function setCookie(){
    var days=c.cookieDuration||7, date=new Date();
    date.setTime(date.getTime()+days*24*60*60*1000);
    d.cookie=c.cookieName+'=1;expires='+date.toUTCString()+';path=/;SameSite=Lax';
  }
  function closePopup(){
    if(!popup) return;
    popup.classList.add('pcc-popup--closing');
    setTimeout(function(){
      popup.style.display='none';
      popup.classList.remove('pcc-popup--closing');
      if(c.position!=='float') d.body.classList.remove('pcc-popup-open');
    },300);
    setCookie();
  }
  function setupFocusTrap(){
    if(!popup) return;
    var el=popup.querySelectorAll('button,[href],input,select,textarea,[tabindex]:not([tabindex="-1"])');
    if(el.length){ firstFocus=el[0]; lastFocus=el[el.length-1]; setTimeout(function(){ if(firstFocus) firstFocus.focus(); },100); }
  }
  function showPopup(){
    if(!popup) return;
    popup.style.display='block';
    if(c.position!=='float') d.body.classList.add('pcc-popup-open');
    setTimeout(function(){ popup.classList.add('pcc-popup--visible'); setupFocusTrap(); },10);
  }
  w.pccShowPopup=showPopup;
  function handleAction(action){
    if(!w.pccConsent) return;
    if(action==='accept-all') w.pccConsent.acceptAll();
    else if(action==='analytics-only') w.pccConsent.acceptAnalyticsOnly();
    else if(action==='decline-all') w.pccConsent.declineAll();
    closePopup();
  }
  function keydown(e){
    if(!popup||popup.style.display==='none') return;
    if(e.key==='Escape'||e.keyCode===27){ e.preventDefault(); closePopup(); return; }
    if(e.key==='Tab'||e.keyCode===9){
      if(e.shiftKey){ if(d.activeElement===firstFocus){ e.preventDefault(); lastFocus.focus(); } }
      else { if(d.activeElement===lastFocus){ e.preventDefault(); firstFocus.focus(); } }
    }
  }
  function init(){
    popup=d.querySelector('.pcc-popup');
    if(!popup) return;
    closeBtn=popup.querySelector('.pcc-popup__close');
    var trigger=d.getElementById('pcc-settings-trigger');
    if(trigger) trigger.addEventListener('click',function(e){ e.preventDefault(); showPopup(); });
    if(c.autoShow&&!hasShownCookie()) setTimeout(showPopup,500);
    if(closeBtn) closeBtn.addEventListener('click',function(e){ e.preventDefault(); closePopup(); });
    popup.addEventListener('click',function(e){ var b=e.target.closest('[data-pcc-action]'); if(b){ e.preventDefault(); handleAction(b.getAttribute('data-pcc-action')); } });
    d.addEventListener('keydown',keydown);
    if(c.repromptDecline){
      d.addEventListener('pcc:changed',function(e){
        var s=e.detail;
        if(!s.analytics&&!s.ads){
          var key='pcc_reprompt_session';
          if(!w.sessionStorage.getItem(key)&&popup&&popup.style.display==='none'){
            w.sessionStorage.setItem(key,'1');
            setTimeout(showPopup,2000);
          }
        }
      });
    }
  }
  if(d.readyState==='loading') d.addEventListener('DOMContentLoaded',init); else init();
})(window,document);
JS;
	}

	public function render_popup() {
		if ( ! $this->is_popup_enabled() ) {
			return;
		}
		$settings  = get_option( 'piratez_cc_settings', piratez_cc_default_settings() );
		$style     = isset( $settings['popup_style'] ) ? sanitize_text_field( $settings['popup_style'] ) : 'minimal';
		$position  = isset( $settings['popup_position'] ) ? sanitize_text_field( $settings['popup_position'] ) : 'float';
		$animation = isset( $settings['popup_animation'] ) ? sanitize_text_field( $settings['popup_animation'] ) : 'slide';
		$title     = ! empty( $settings['popup_title'] ) ? $settings['popup_title'] : __( 'Cookie Consent', 'piratez-cookie-consent' );
		$message   = ! empty( $settings['popup_message'] ) ? $settings['popup_message'] : __( 'We use cookies to enhance your browsing experience and analyze our traffic.', 'piratez-cookie-consent' );
		$classes   = array(
			'pcc-popup',
			'pcc-popup--style-' . $style,
			'pcc-popup--position-' . $position,
			'pcc-popup--animation-' . $animation,
		);
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			role="dialog"
			aria-modal="true"
			aria-labelledby="pcc-popup-title"
			aria-describedby="pcc-popup-message"
			style="display: none;">
			<div class="pcc-popup__overlay" aria-hidden="true"></div>
			<div class="pcc-popup__container">
				<div class="pcc-popup__content">
					<button type="button" class="pcc-popup__close" aria-label="<?php esc_attr_e( 'Close consent popup', 'piratez-cookie-consent' ); ?>"><span aria-hidden="true">&times;</span></button>
					<h2 id="pcc-popup-title" class="pcc-popup__title"><?php echo esc_html( $title ); ?></h2>
					<p id="pcc-popup-message" class="pcc-popup__message"><?php echo esc_html( $message ); ?></p>
					<div class="pcc-popup__actions">
						<button type="button" class="pcc-popup__button pcc-popup__button--primary" data-pcc-action="accept-all" aria-label="<?php esc_attr_e( 'Accept all cookies', 'piratez-cookie-consent' ); ?>"><?php esc_html_e( 'Accept All', 'piratez-cookie-consent' ); ?></button>
						<button type="button" class="pcc-popup__button pcc-popup__button--secondary" data-pcc-action="analytics-only" aria-label="<?php esc_attr_e( 'Accept analytics cookies only', 'piratez-cookie-consent' ); ?>"><?php esc_html_e( 'Analytics Only', 'piratez-cookie-consent' ); ?></button>
						<button type="button" class="pcc-popup__button pcc-popup__button--tertiary" data-pcc-action="decline-all" aria-label="<?php esc_attr_e( 'Decline all cookies', 'piratez-cookie-consent' ); ?>"><?php esc_html_e( 'Decline All', 'piratez-cookie-consent' ); ?></button>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	public function render_settings_trigger() {
		if ( ! $this->is_popup_enabled() ) {
			return;
		}
		?>
		<button type="button" class="pcc-settings-trigger" id="pcc-settings-trigger" aria-label="<?php esc_attr_e( 'Cookie settings', 'piratez-cookie-consent' ); ?>">
			<?php esc_html_e( 'Cookie settings', 'piratez-cookie-consent' ); ?>
		</button>
		<?php
	}
}
