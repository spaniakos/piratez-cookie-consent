# PirateZ Cookie Consent

Google Consent Mode v2 with default-denied consent, explicit visitor choices and a settings popup.

## PirateZ admin menu

Version 1.2.0 uses **PirateZ → Cookie Consent**. The menu works without Manager or a PirateZ theme and reuses Manager’s parent when present. Old Tools links redirect automatically; saved settings and frontend consent behavior are unchanged. The bundled GitHub updater continues to work independently through WordPress.

## Integration check

Install Playwright 1.62.1 and @wp-playground/cli 3.1.58, set WP_SOURCE to the supplied WordPress 7.1.3 source, PIRATEZ_MANAGER_SOURCE to Manager’s source tree, and optionally PIRATEZ_CHROMIUM_EXECUTABLE, then run node tests/wordpress.cjs. Tests create a disposable site and never touch production.

See readme.txt for plugin instructions and RELEASING.md for packaging.
