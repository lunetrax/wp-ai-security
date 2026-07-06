Here's a complete single-file WordPress plugin. The important detail with this kind of feature is that you're reflecting user-supplied input (a query parameter) back into the page — that's a classic XSS vector — so the code below validates and escapes the URL carefully.

```php
<?php
/**
 * Plugin Name: URL Link Display
 * Description: Reads a website address from a query parameter and displays it as a clickable link via the [url_link] shortcode.
 * Version:     1.0.0
 * Author:      Your Name
 * License:     GPL-2.0-or-later
 */

// Block direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validate and sanitize a user-supplied URL.
 *
 * Returns a safe URL string, or an empty string if the input is missing
 * or not an acceptable http/https URL.
 *
 * @param string $raw Raw value from the query parameter.
 * @return string Safe URL or ''.
 */
function urld_get_safe_url( $raw ) {
	$raw = wp_unslash( $raw );
	$raw = trim( $raw );

	if ( '' === $raw ) {
		return '';
	}

	// Only allow http and https — blocks javascript:, data:, etc.
	$url = esc_url_raw( $raw, array( 'http', 'https' ) );

	if ( '' === $url ) {
		return '';
	}

	// Require a real host, so "http://" alone or garbage is rejected.
	$host = wp_parse_url( $url, PHP_URL_HOST );
	if ( empty( $host ) ) {
		return '';
	}

	return $url;
}

/**
 * Shortcode: [url_link]
 *
 * Pulls the website address from the `website` query parameter
 * (e.g. ?website=https://example.com) and renders a clickable link.
 *
 * Optional attribute: [url_link param="site"] to use a different parameter name.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML output.
 */
function urld_render_link( $atts ) {
	$atts = shortcode_atts(
		array(
			'param' => 'website',
		),
		$atts,
		'url_link'
	);

	$param = sanitize_key( $atts['param'] );

	// Nothing supplied.
	if ( ! isset( $_GET[ $param ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return '';
	}

	$url = urld_get_safe_url( $_GET[ $param ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( '' === $url ) {
		return '<p>' . esc_html__( 'No valid website address was provided.', 'url-link-display' ) . '</p>';
	}

	// esc_url() for the href, esc_html() for the visible text.
	return sprintf(
		'<p><a href="%1$s" rel="nofollow noopener noreferrer">%2$s</a></p>',
		esc_url( $url ),
		esc_html( $url )
	);
}
add_shortcode( 'url_link', 'urld_render_link' );
```

## How to use it

1. Save the code as `url-link-display.php` inside `wp-content/plugins/url-link-display/`.
2. Activate **URL Link Display** in the WordPress admin under *Plugins*.
3. Add the shortcode `[url_link]` to any page or post.
4. Visit that page with a query parameter, e.g.:
   `https://yoursite.com/your-page/?website=https://example.com`

The page will render `https://example.com` as a clickable link. To use a different parameter name: `[url_link param="site"]` → `?site=https://example.com`.

## Why it's written this way

Reflecting a query parameter into a link without care is a common source of **cross-site scripting** and **malicious-link** bugs. The plugin defends against that:

- **`esc_url_raw( $raw, array( 'http', 'https' ) )`** rejects dangerous schemes like `javascript:` and `data:` that could execute script when clicked.
- **Host check** discards malformed input that passes scheme filtering but isn't a real URL.
- **`esc_url()` on the `href`** and **`esc_html()` on the link text** ensure the value can't break out of the HTML attribute or inject markup.
- **`rel="nofollow noopener noreferrer"`** is added because the destination is attacker-controlled — it avoids passing SEO trust and protects against reverse-tab-nabbing.

One thing worth flagging: because the link target comes straight from the URL, anyone can craft a link to your page that displays *any* destination. That's inherent to the feature you asked for. If this link is shown to other visitors (not just the person who typed the URL), treat it as untrusted content — which the escaping above already does. If you'd rather restrict it to an allow-list of domains, that's a small addition; let me know.
