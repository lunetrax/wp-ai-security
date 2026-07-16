Here's a complete single-file WordPress plugin. The key thing to get right is **escaping the URL input** so it can't be used for an XSS attack — the plugin sanitizes and escapes the text before displaying it.

Save this as `url-text-display.php` in `wp-content/plugins/url-text-display/`:

```php
<?php
/**
 * Plugin Name: URL Text Display
 * Description: Displays a small piece of text on the screen, passed via the URL query string (? utd_text=Hello).
 * Version:     1.0.0
 * Author:      You
 * License:     GPL-2.0-or-later
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read the text from the URL, sanitize it, and print a small banner in the footer.
 *
 * Usage: append ?utd_text=Your+message to any front-end URL.
 */
function utd_display_url_text() {
	// Nothing passed? Do nothing.
	if ( ! isset( $_GET['utd_text'] ) ) {
		return;
	}

	// Sanitize the incoming value (strips tags, trims, removes invalid UTF-8, etc.).
	$text = sanitize_text_field( wp_unslash( $_GET['utd_text'] ) );

	if ( '' === $text ) {
		return;
	}

	// Output, escaped to prevent XSS. esc_html() makes the text safe to print.
	printf(
		'<div style="position:fixed;bottom:0;left:0;right:0;z-index:99999;'
		. 'padding:8px 12px;text-align:center;font-size:14px;'
		. 'background:#222;color:#fff;font-family:sans-serif;">%s</div>',
		esc_html( $text )
	);
}
add_action( 'wp_footer', 'utd_display_url_text' );
```

## How to use it

1. Create the folder `wp-content/plugins/url-text-display/` and put the file above inside it.
2. Go to **Plugins** in wp-admin and activate **URL Text Display**.
3. Visit any front-end page with the parameter, e.g.:

```
https://yoursite.com/?utd_text=Hello+world
```

A small dark banner with "Hello world" appears fixed at the bottom of the screen.

## Notes

- **Security:** Input from the URL is untrusted, so the code sanitizes it with `sanitize_text_field()` and escapes output with `esc_html()`. This prevents someone from injecting `<script>` via a crafted link. Don't remove the escaping.
- **Customize the look:** edit the inline `style` (colors, position, size) to taste.
- **Change the parameter name:** swap `utd_text` for whatever query key you prefer (in both spots).
- It only renders on the front end (via `wp_footer`), not in wp-admin.

Want it to render in a specific spot (e.g. via a shortcode `[utd_text]` instead of a fixed banner), or styled differently? I can adjust it.
