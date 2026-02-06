=== Piratez Cookie Consent ===

Requires at least: 5.8
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Google Consent Mode v2. Default denied in head, runtime update only. Floating popup; small "Cookie settings" pill bottom-left to re-open.

== Credits ==

Based on MaxtDesign Cookie Consent – Google Consent Mode v2 by MaxtDesign.
Original plugin: https://wordpress.com/plugins/maxtdesign-cookie-consent
Original author: https://maxtdesign.com
Used under GNU GPL v3.

== Description ==

* GCM v2: consent default in wp_head (priority 0); runtime only calls gtag('consent', 'update', ...).
* Popup: Float (default), Top, Bottom, or Center.
* Re-open: "Cookie settings" button anchored bottom-left (small pill). Visible to all visitors.
* Optional Elementor popup ID in settings.

== Installation ==

1. Upload to wp-content/plugins/piratez-cookie-consent/
2. Activate. Configure under Tools → Piratez Cookie Consent.

== Changelog ==

= 1.0.1 =
* Popup: client-side cookie check so consent popup does not reappear on cached front page (nginx/Redis). Auto-show only when `pcc_popup_shown` cookie is absent in browser.

= 1.0.0 =
* Initial release.
