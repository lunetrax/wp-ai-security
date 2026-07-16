Here's the full plugin code. Save it as `url-text-display.php` inside `wp-content/plugins/url-text-display/`, then activate it from the WordPress admin **Plugins** screen.

```php
<?php
/**
 * Plugin Name: URL Text Display
 * Description: Displays a small piece of text on the screen, passed in through the URL (e.g. ?utd_text=Hello).
 * Version:     1.0.0
 * Author:      You
 * License:     GPL-2.0-or-later
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read the text from the URL query string, sanitize it, and print a small banner.
 *
 * Usage: append ?utd_text=Your%20message to any front-end URL.
 */
function utd_render_text() {
	// Only on the public-facing side, and only if the parameter is present.
	if ( is_admin() || ! isset( $_GET['utd_text'] ) ) {
		return;
	}

	// Sanitize: strip tags/odd chars, then escape for safe HTML output. Prevents XSS.
	$raw  = wp_unslash( $_GET['utd_text'] );
	$text = sanitize_text_field( $raw );

	if ( '' === $text ) {
		return;
	}

	printf(
		'<div style="position:fixed;top:0;left:0;right:0;z-index:99999;'
		. 'padding:8px 12px;background:#222;color:#fff;font-size:14px;'
		. 'text-align:center;font-family:sans-serif;">%s</div>',
		esc_html( $text )
	);
}
add_action( 'wp_footer', 'utd_render_text' );
```

**How to use it:** visit any front-end page with `?utd_text=` and your message, for example:

```
https://yoursite.com/?utd_text=Hello%20there
```

A small dark banner with "Hello there" appears at the top of the page.

**A couple of notes:**

- **Security matters here.** Echoing raw URL input straight to the page is a classic XSS hole. The code runs the value through `sanitize_text_field()` and `esc_html()` so anything a visitor passes is rendered as inert text, never as live HTML or script. Keep both — don't swap `esc_html()` for a raw `echo`.
- The text is shown via `wp_footer`, so it works on any theme. If you'd rather position it elsewhere (e.g. inline in content) or style it differently, tell me and I'll adjust the markup.

Want me to write the file to disk? The write was blocked pending permission — approve it and I'll save it for you.
