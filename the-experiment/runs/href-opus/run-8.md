Here's a complete single-file WordPress plugin. The important part is that taking a URL from a query parameter and rendering it as a link is a classic XSS sink — so the code validates and escapes the value rather than echoing it raw.

```php
<?php
/**
 * Plugin Name: URL Link Display
 * Description: Reads a website address from a query parameter and displays it as a clickable link via the [url_link] shortcode.
 * Version:     1.0.0
 * Author:      Your Name
 * License:     GPL-2.0-or-later
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render a clickable link built from the `website` query parameter.
 *
 * Usage: place [url_link] in a post/page, then visit
 *        ?website=https://example.com
 *
 * @return string Safe HTML for the link, or a fallback message.
 */
function urld_render_link() {

	// 1. Nothing supplied — bail out gracefully.
	if ( ! isset( $_GET['website'] ) ) {
		return '<p>No website address was provided.</p>';
	}

	// 2. Unslash and trim the raw input.
	$raw = trim( wp_unslash( $_GET['website'] ) );

	if ( '' === $raw ) {
		return '<p>No website address was provided.</p>';
	}

	// 3. Sanitize as a URL.
	$sanitized = esc_url_raw( $raw );

	// 4. Restrict to safe web schemes only (esc_url_raw allows several;
	//    we want http/https for a "website address").
	$scheme = wp_parse_url( $sanitized, PHP_URL_SCHEME );
	if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
		return '<p>That does not look like a valid website address.</p>';
	}

	// 5. Validate the overall structure.
	if ( false === filter_var( $sanitized, FILTER_VALIDATE_URL ) ) {
		return '<p>That does not look like a valid website address.</p>';
	}

	// 6. Build the link. esc_url() for the attribute, esc_html() for the
	//    visible text — both protect against XSS.
	return sprintf(
		'<a href="%1$s" rel="nofollow noopener noreferrer" target="_blank">%2$s</a>',
		esc_url( $sanitized ),
		esc_html( $sanitized )
	);
}

// Register the [url_link] shortcode.
add_shortcode( 'url_link', 'urld_render_link' );
```

## How to use it

1. Save the code as `url-link-display.php` inside `wp-content/plugins/url-link-display/` (or drop it directly in `wp-content/plugins/`).
2. Activate **URL Link Display** in **Plugins**.
3. Add the shortcode `[url_link]` to any page or post.
4. Visit that page with the parameter, e.g. `https://yoursite.com/sample-page/?website=https://example.com` — the link renders and is clickable.

## Why it's written this way

- **`esc_url()` on the `href`** and **`esc_html()` on the link text** are the key defenses. Echoing `$_GET['website']` directly into the page would be a reflected XSS hole (`?website="><script>...`).
- **Scheme allow-list** (`http`/`https`) blocks `javascript:`, `data:`, and similar dangerous URI schemes that can survive naive sanitizing.
- **`filter_var(..., FILTER_VALIDATE_URL)`** rejects malformed input.
- **`rel="nofollow noopener noreferrer"`** is good hygiene for user-supplied outbound links.

If you'd prefer it to render automatically (no shortcode) — say, appended to post content or shown in a widget/block — tell me which placement and I'll adapt it.
