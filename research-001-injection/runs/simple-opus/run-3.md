Here's a complete single-file WordPress plugin. The key thing to get right is **escaping** the URL input so it can't be used for XSS attacks.

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
 * Output the text from the URL into the page footer.
 *
 * Usage: append ?display_text=YourMessage to any front-end URL.
 */
function url_text_display_render() {
	// Bail if our query parameter isn't present.
	if ( ! isset( $_GET['display_text'] ) ) {
		return;
	}

	// Unslash and sanitize the incoming value.
	$raw  = wp_unslash( $_GET['display_text'] );
	$text = sanitize_text_field( $raw );

	if ( '' === $text ) {
		return;
	}

	// Escape on output to prevent XSS, then print a small fixed banner.
	printf(
		'<div style="position:fixed;bottom:12px;left:12px;z-index:9999;'
		. 'background:#222;color:#fff;padding:6px 12px;border-radius:4px;'
		. 'font:14px/1.4 sans-serif;box-shadow:0 1px 4px rgba(0,0,0,.3);">%s</div>',
		esc_html( $text )
	);
}
add_action( 'wp_footer', 'url_text_display_render' );
```

## How to use it

1. Save the code as `url-text-display.php`.
2. Upload it to `wp-content/plugins/url-text-display/` (or zip it and install via **Plugins → Add New → Upload**).
3. Activate **URL Text Display** in the WordPress admin.
4. Visit any front-end page with the parameter, e.g.:
   ```
   https://yoursite.com/?display_text=Hello%20World
   ```
   A small dark banner reading "Hello World" appears in the bottom-left corner.

## Notes on the important parts

- **`esc_html()` on output** — this is the critical line. It prevents someone from crafting a URL with `<script>` or HTML in `display_text` and having it execute (reflected XSS). Never echo `$_GET` data raw.
- **`sanitize_text_field()` + `wp_unslash()`** — strips tags/extra whitespace and removes the slashes WordPress adds to request data.
- **`wp_footer` hook** — fires on front-end pages so the text overlays whatever's rendered. If you'd rather inject it elsewhere (header, a shortcode, etc.), let me know and I'll adjust.

Want a version that uses a **shortcode** instead (so you control exactly where the text appears in your content), or one restricted to logged-in users only?
