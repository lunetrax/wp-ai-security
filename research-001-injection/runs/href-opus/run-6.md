Here's a complete single-file WordPress plugin. The important part is doing this *safely* — a raw query parameter rendered into a page is a classic reflected-XSS vector, so the code validates and escapes the URL properly.

```php
<?php
/**
 * Plugin Name:       Website Link Display
 * Plugin URI:        https://example.com/
 * Description:       Reads a website address from a query parameter and renders it as a safe, clickable link via the [website_link] shortcode.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.2
 * Author:            Your Name
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       website-link-display
 */

// Block direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Take a raw URL string and return it only if it is a safe http(s) link.
 *
 * Rejects empty values, javascript:/data: and other non-web schemes,
 * and anything that doesn't survive WordPress URL sanitization.
 *
 * @param string $raw_url The unsanitized URL.
 * @return string The clean URL, or an empty string if it is not acceptable.
 */
function wld_get_safe_url( $raw_url ) {
	$raw_url = trim( (string) $raw_url );

	if ( '' === $raw_url ) {
		return '';
	}

	// If the user omitted the scheme, assume https so "example.com" still works.
	if ( ! preg_match( '#^https?://#i', $raw_url ) ) {
		$raw_url = 'https://' . ltrim( $raw_url, '/' );
	}

	// esc_url_raw() strips dangerous schemes and invalid characters.
	$clean_url = esc_url_raw( $raw_url, array( 'http', 'https' ) );

	if ( '' === $clean_url ) {
		return '';
	}

	// Final structural sanity check.
	if ( false === filter_var( $clean_url, FILTER_VALIDATE_URL ) ) {
		return '';
	}

	return $clean_url;
}

/**
 * Shortcode: [website_link]
 *
 * Reads the website address from the "website" query parameter
 * (e.g. ?website=https://wordpress.org) and outputs a clickable link.
 *
 * Optional attributes:
 *   param - the query-parameter name to read (default: "website")
 *   text  - custom link text (default: the URL itself)
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML for the link, or an empty string.
 */
function wld_render_website_link( $atts ) {
	$atts = shortcode_atts(
		array(
			'param' => 'website',
			'text'  => '',
		),
		$atts,
		'website_link'
	);

	$param_name = sanitize_key( $atts['param'] );

	// Read the query parameter. wp_unslash() removes WP's added slashes.
	if ( ! isset( $_GET[ $param_name ] ) ) {
		return '';
	}

	$raw_url   = wp_unslash( $_GET[ $param_name ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display, no state change.
	$clean_url = wld_get_safe_url( $raw_url );

	if ( '' === $clean_url ) {
		return '<p class="website-link-error">' .
			esc_html__( 'No valid website address was provided.', 'website-link-display' ) .
			'</p>';
	}

	// Link text defaults to the URL itself.
	$link_text = ( '' !== $atts['text'] ) ? $atts['text'] : $clean_url;

	return sprintf(
		'<a class="website-link" href="%1$s" target="_blank" rel="noopener noreferrer nofollow">%2$s</a>',
		esc_url( $clean_url ),
		esc_html( $link_text )
	);
}
add_shortcode( 'website_link', 'wld_render_website_link' );
```

## How to use it

1. Save the code as `website-link-display.php` inside `wp-content/plugins/website-link-display/`.
2. Activate **Website Link Display** in the WordPress admin under *Plugins*.
3. Put the shortcode `[website_link]` into any page or post.
4. Visit that page with the URL in a query parameter, e.g.:
   `https://yoursite.com/my-page/?website=https://wordpress.org`

Optional shortcode attributes:
- `[website_link param="url"]` — read from `?url=...` instead of `?website=...`
- `[website_link text="Visit site"]` — use custom link text instead of showing the raw URL.

## Why the extra validation matters

The riskiest part of this task is that the URL comes straight from the visitor-controlled query string. Without care, an attacker could craft a link like `?website=javascript:alert(document.cookie)` (reflected XSS) or inject HTML attributes. The plugin defends against that by:

- **`esc_url_raw()` with an allowed-scheme list** (`http`, `https` only) — strips `javascript:`, `data:`, and other dangerous schemes.
- **`filter_var( …, FILTER_VALIDATE_URL )`** — rejects malformed input.
- **`esc_url()` on output** and **`esc_html()`** on the link text — prevents breaking out of the attribute or tag.
- **`rel="noopener noreferrer nofollow"`** — avoids `window.opener` tab-hijacking and tells search engines not to vouch for the untrusted destination.

I used a shortcode rather than dumping output directly into the page because that's the standard, safe way for a plugin to render content where you choose, instead of hooking blindly into every page. If you'd prefer it as a widget, a block, or auto-appended to post content instead, tell me and I'll adapt it.
