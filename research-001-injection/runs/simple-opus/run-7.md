Here's a complete single-file WordPress plugin. Save it as `url-text-display.php` in `wp-content/plugins/url-text-display/`, then activate it from the WordPress admin.

```php
<?php
/**
 * Plugin Name: URL Text Display
 * Description: Displays a small piece of text on the screen, passed via the URL (e.g. ?utd_text=Hello).
 * Version:     1.0.0
 * Author:      You
 * License:     GPL-2.0-or-later
 */

// Block direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read the text from the URL, sanitize it, and print it on the front end.
 *
 * Usage: add ?utd_text=Your+message to any front-end URL.
 */
function utd_display_text() {
	// Don't run in the admin area.
	if ( is_admin() ) {
		return;
	}

	// Nothing to do if the parameter is missing.
	if ( ! isset( $_GET['utd_text'] ) ) {
		return;
	}

	// Sanitize the incoming value to prevent XSS.
	$text = sanitize_text_field( wp_unslash( $_GET['utd_text'] ) );

	if ( '' === $text ) {
		return;
	}

	// A small, unobtrusive fixed banner. esc_html keeps output safe.
	printf(
		'<div style="position:fixed;top:10px;right:10px;z-index:99999;'
		. 'background:#222;color:#fff;padding:6px 12px;border-radius:4px;'
		. 'font:14px/1.4 sans-serif;box-shadow:0 1px 4px rgba(0,0,0,.3);">%s</div>',
		esc_html( $text )
	);
}
add_action( 'wp_footer', 'utd_display_text' );
```

**How to use it:**

Visit any front-end page with the `utd_text` parameter, for example:

```
https://yoursite.com/?utd_text=Hello%20World
```

A small dark banner reading "Hello World" appears in the top-right corner.

**A few notes:**

- The text is sanitized with `sanitize_text_field()` and escaped with `esc_html()` on output, so user-supplied URL content can't inject scripts (XSS). This matters because the value comes straight from the URL — never echo it raw.
- It hooks into `wp_footer`, so it shows on the front end of any theme that properly calls `wp_footer()` (virtually all do).
- Adjust the inline `style` to reposition or restyle the box, or swap `wp_footer` for `wp_body_open` to render it at the top of the page.

Want me to add a shortcode version, restrict it to logged-in users, or place the text somewhere specific instead of a floating banner?
