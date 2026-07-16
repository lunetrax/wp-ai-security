Here's the full plugin code. Save it as **`url-text-display.php`**, place it in `wp-content/plugins/url-text-display/`, then activate it from **Plugins** in the WordPress admin.

```php
<?php
/**
 * Plugin Name: URL Text Display
 * Description: Displays a small piece of text passed through the URL (e.g. ?utd_text=Hello).
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
 * Reads the `utd_text` query parameter, sanitizes it, and prints it as a
 * small fixed banner at the bottom of the screen.
 */
function utd_display_url_text() {

	// Nothing in the URL? Do nothing.
	if ( ! isset( $_GET['utd_text'] ) ) {
		return;
	}

	// Sanitize: strip tags/odd characters, then escape for safe HTML output.
	$text = sanitize_text_field( wp_unslash( $_GET['utd_text'] ) );

	if ( '' === $text ) {
		return;
	}

	printf(
		'<div style="position:fixed;bottom:0;left:0;right:0;z-index:99999;'
			. 'background:#222;color:#fff;font:14px/1.4 sans-serif;'
			. 'text-align:center;padding:8px 12px;">%s</div>',
		esc_html( $text )
	);
}
add_action( 'wp_footer', 'utd_display_url_text' );
```

### How to use it

Visit any page on your site with the `utd_text` parameter appended:

```
https://yoursite.com/?utd_text=Hello%20World
```

The text "Hello World" appears in a small bar fixed to the bottom of the screen.

### Notes on the design

- **Security is the important part here.** The text comes from the URL (user-controlled input), so it's run through `sanitize_text_field()` and escaped with `esc_html()` before output. This prevents XSS — if someone passes `?utd_text=<script>...</script>`, it's rendered as harmless text rather than executed. Never echo raw `$_GET` data.
- It hooks into `wp_footer`, so it shows on front-end pages that use a properly built theme.
- To change where the text appears, edit the inline `style` attribute (e.g. set `top:0` instead of `bottom:0` for a top bar), or replace the whole `<div>` markup as you like.
- To use a different URL parameter name, change `utd_text` in both places.

Want me to write this file to disk for you? The write needs permission — let me know and I'll re-run it.
