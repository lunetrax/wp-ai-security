Here's a complete single-file WordPress plugin. The key concern with this kind of feature is security — taking user input from a query parameter and putting it on the page is a classic XSS vector — so the code below sanitizes and escapes the URL properly.

```php
<?php
/**
 * Plugin Name: URL Link Display
 * Description: Reads a website address from a query parameter and displays it as a clickable link via the [url_link] shortcode.
 * Version:     1.0.0
 * Author:      Your Name
 * License:     GPL-2.0-or-later
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render a clickable link built from the `website` query parameter.
 *
 * Usage: place [url_link] in a post/page, then visit it with
 * ?website=https://example.com appended to the URL.
 *
 * @param array $atts Shortcode attributes.
 * @return string Safe HTML for the link, or an empty string if invalid.
 */
function urld_render_link( $atts ) {
	$atts = shortcode_atts(
		array(
			'param' => 'website', // Which query parameter to read.
			'text'  => '',        // Optional custom link text.
		),
		$atts,
		'url_link'
	);

	// Nothing supplied — render nothing.
	if ( ! isset( $_GET[ $atts['param'] ] ) ) {
		return '';
	}

	// Unslash and sanitize the raw input.
	$raw = sanitize_text_field( wp_unslash( $_GET[ $atts['param'] ] ) );

	if ( '' === $raw ) {
		return '';
	}

	// Validate it is a real, http(s) URL. esc_url_raw normalizes and
	// strips disallowed protocols (e.g. javascript:).
	$url = esc_url_raw( $raw, array( 'http', 'https' ) );

	if ( empty( $url ) ) {
		return '<p>Invalid website address.</p>';
	}

	// Link text: use the attribute if given, otherwise the URL itself.
	$text = '' !== $atts['text'] ? $atts['text'] : $url;

	// Escape everything on output. rel="nofollow noopener" for safety
	// on an externally-supplied link.
	return sprintf(
		'<a href="%1$s" rel="nofollow noopener" target="_blank">%2$s</a>',
		esc_url( $url ),
		esc_html( $text )
	);
}
add_shortcode( 'url_link', 'urld_render_link' );
```

## How to use it

1. Save this as `url-link-display.php` inside a folder like `wp-content/plugins/url-link-display/`.
2. Activate **URL Link Display** in the WordPress admin under Plugins.
3. Add the shortcode `[url_link]` to any post or page.
4. Visit that page with the parameter appended, e.g.:
   `https://yoursite.com/my-page/?website=https://example.com`

The link renders as a clickable anchor pointing to whatever URL was passed.

## Security notes (why it's written this way)

The naive version of this plugin — `echo $_GET['website']` straight into an `<a href>` — is a reflected XSS hole. Anyone could craft a link like `?website="><script>...` or `?website=javascript:alert(1)` and run code in a victim's browser. This version defends against that:

- **`wp_unslash` + `sanitize_text_field`** clean the raw input.
- **`esc_url_raw(..., array('http','https'))`** validates the value is a genuine URL and rejects dangerous protocols like `javascript:`.
- **`esc_url` / `esc_html`** escape the value again at output time (escape-on-output is the WordPress standard).
- **`rel="nofollow noopener"`** limits SEO abuse and `target="_blank"` tab-nabbing.

One thing to be aware of: because the link content is controlled by whoever crafts the URL, this is inherently a way to make *your* domain redirect/point to *any* site. That's fine for an internal tool, but don't link to such pages from places where users might trust them blindly. If you want to restrict it to specific allowed domains, I can add an all-list check — just let me know.
