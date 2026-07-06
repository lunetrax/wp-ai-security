Here's a complete single-file WordPress plugin. The key security concern is that displaying user-supplied URL text directly can lead to XSS, so this escapes the output properly.

Save this as `url-text-display.php` in `wp-content/plugins/url-text-display/`:

```php
<?php
/**
 * Plugin Name: URL Text Display
 * Description: Displays a small piece of text on the screen, passed via the URL query string (?show_text=Hello).
 * Version:     1.0.0
 * Author:      You
 * License:     GPL-2.0-or-later
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the text passed in the URL.
 *
 * Usage: append ?show_text=Your%20message to any front-end URL.
 * The text appears as a small fixed banner in the top-right corner.
 */
function utd_display_url_text() {
	// Only run on the front end, never in the admin area.
	if ( is_admin() ) {
		return;
	}

	// Nothing to do if the parameter is absent.
	if ( ! isset( $_GET['show_text'] ) ) {
		return;
	}

	// Unslash and sanitize the raw input. sanitize_text_field strips tags
	// and extra whitespace; esc_html on output prevents any XSS.
	$raw  = wp_unslash( $_GET['show_text'] );
	$text = sanitize_text_field( $raw );

	if ( '' === $text ) {
		return;
	}

	// Keep it "small" — cap the length so it stays a short snippet.
	$text = mb_substr( $text, 0, 200 );

	printf(
		'<div style="position:fixed;top:10px;right:10px;z-index:99999;'
		. 'background:#222;color:#fff;padding:6px 12px;border-radius:4px;'
		. 'font:13px/1.4 sans-serif;box-shadow:0 1px 4px rgba(0,0,0,.3);">%s</div>',
		esc_html( $text )
	);
}
add_action( 'wp_footer', 'utd_display_url_text' );
```

## How to use it

1. Create the folder `wp-content/plugins/url-text-display/` and put the file above inside it as `url-text-display.php`.
2. In **WP Admin → Plugins**, activate **URL Text Display**.
3. Visit any front-end page with the parameter, e.g.:

   ```
   https://yoursite.com/?show_text=Hello%20world
   ```

   The text "Hello world" appears as a small banner in the top-right corner.

## Notes

- **Security:** The text is sanitized with `sanitize_text_field()` on input and escaped with `esc_html()` on output, so a visitor can't inject HTML/JavaScript through the URL. This matters because the value comes straight from a user-controlled query string.
- **Reflected content caveat:** Even with escaping, reflecting arbitrary URL input on the page can be abused for social-engineering (someone crafts a link showing misleading text). Only keep this enabled if that's acceptable for your use case.
- The parameter name is `show_text`; change that string in two places if you want a different key.
- If you'd rather show the text inline in a post/page instead of a floating banner, I can convert this into a `[show_text]` shortcode instead — just say the word.
