Here's a complete single-file WordPress plugin. Save it as `url-text-display.php` and place it in `wp-content/plugins/url-text-display/` (or zip it and upload via the admin).

```php
<?php
/**
 * Plugin Name: URL Text Display
 * Description: Displays a small piece of text passed through the URL (e.g. ?display_text=Hello).
 * Version:     1.0.0
 * Author:      You
 * License:     GPL-2.0-or-later
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the text from the URL query parameter onto the front-end.
 *
 * Usage: append ?display_text=Your+message to any front-end URL.
 */
function utd_display_text_from_url() {
	// Only run on the front end.
	if ( is_admin() ) {
		return;
	}

	// Bail if the parameter isn't present.
	if ( ! isset( $_GET['display_text'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	// Sanitize the incoming text to prevent XSS.
	$text = sanitize_text_field( wp_unslash( $_GET['display_text'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( '' === $text ) {
		return;
	}

	// Output a small, unobtrusive banner with the escaped text.
	printf(
		'<div style="position:fixed;top:0;left:0;right:0;z-index:99999;padding:8px 12px;background:#0073aa;color:#fff;font:14px/1.4 sans-serif;text-align:center;">%s</div>',
		esc_html( $text )
	);
}
add_action( 'wp_footer', 'utd_display_text_from_url' );
```

How it works:

- **Activate** the plugin from the WordPress admin (*Plugins* screen).
- Visit any front-end page with the parameter, e.g.:
  `https://yoursite.com/?display_text=Hello%20World`
- The text appears as a small fixed banner at the top of the page.

Security notes:

- `sanitize_text_field()` strips tags/extra whitespace from the input, and `esc_html()` escapes it on output — together these prevent XSS, so arbitrary HTML/script in the URL won't execute.
- It only renders on the front end and only when `display_text` is supplied.

If you'd prefer it shown inline within post content (via a shortcode) or styled differently (e.g. centered in the page rather than a top banner), let me know and I'll adjust it.
