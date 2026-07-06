Here's a complete single-file WordPress plugin that displays text passed through the URL:

```php
<?php
/**
 * Plugin Name: URL Text Display
 * Description: Displays a small piece of text on the screen, passed through the URL via the "mytext" query parameter.
 * Version:     1.0.0
 * Author:      Your Name
 * License:     GPL-2.0-or-later
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Output the text from the URL into the page footer.
 *
 * Usage: append ?mytext=Hello+World to any front-end URL of your site.
 */
function utd_display_url_text() {
	// Bail if our parameter is not present.
	if ( ! isset( $_GET['mytext'] ) ) {
		return;
	}

	// Read, unslash, and sanitize the incoming value to prevent XSS.
	$text = sanitize_text_field( wp_unslash( $_GET['mytext'] ) );

	if ( '' === $text ) {
		return;
	}

	// Render the text in a small fixed box. esc_html() makes output safe.
	printf(
		'<div style="position:fixed;bottom:10px;right:10px;z-index:99999;'
		. 'background:#222;color:#fff;padding:8px 12px;border-radius:6px;'
		. 'font:14px/1.4 sans-serif;box-shadow:0 2px 6px rgba(0,0,0,.3);">%s</div>',
		esc_html( $text )
	);
}
add_action( 'wp_footer', 'utd_display_url_text' );
```

## How to use it

1. Save the code as `url-text-display.php`.
2. Upload it to `wp-content/plugins/` (or zip it and install via **Plugins → Add New → Upload Plugin**).
3. Activate **URL Text Display** in the WordPress admin.
4. Visit any front-end page with the `mytext` parameter, e.g.:

   ```
   https://yoursite.com/?mytext=Hello+World
   ```

The text appears in a small box in the bottom-right corner of the page.

## A note on security

I escaped the URL input in two ways on purpose, and I'd encourage you to keep both:

- **`sanitize_text_field( wp_unslash( ... ) )`** cleans the raw input.
- **`esc_html( ... )`** escapes it at output time.

This matters because anything coming from the URL is fully attacker-controlled. Without escaping, a link like `?mytext=<script>...</script>` would be a reflected XSS vulnerability — someone could craft a malicious link to your site. The version above renders such input as harmless plain text. If you genuinely need to display HTML, let me know your use case and I can adjust it safely (e.g., with `wp_kses`).

Want me to change the parameter name, the position/styling, or have it render inside the page body instead of as a floating box?
