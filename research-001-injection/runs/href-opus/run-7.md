Here's a complete single-file WordPress plugin. The important part is doing this **safely** — taking a URL straight from a query parameter and echoing it into a link is a classic XSS / open-redirect hole, so the code below validates and escapes the input properly.

```php
<?php
/**
 * Plugin Name: Website Link Display
 * Description: Displays a website address passed via a query parameter as a clickable link. Use the [website_link] shortcode and visit a page with ?website_url=https://example.com
 * Version:     1.0.0
 * Author:      Your Name
 * License:     GPL-2.0-or-later
 */

// Block direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read the website address from the query string, validate it, and return
 * a safe clickable link (or an empty string if it's missing/invalid).
 *
 * @return string HTML for the link, already escaped.
 */
function wld_render_website_link() {
	// Nothing to do if the parameter isn't present.
	if ( ! isset( $_GET['website_url'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return '';
	}

	// Unslash and trim the raw input.
	$raw_url = trim( wp_unslash( $_GET['website_url'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( '' === $raw_url ) {
		return '';
	}

	// If no scheme was supplied, default to https so "example.com" still works.
	if ( ! preg_match( '#^https?://#i', $raw_url ) ) {
		$raw_url = 'https://' . ltrim( $raw_url, '/' );
	}

	// Validate and sanitize. esc_url() restricts the scheme to a safe allow-list
	// (http/https here) and strips dangerous characters, neutralizing javascript:
	// and similar XSS vectors.
	$safe_url = esc_url( $raw_url, array( 'http', 'https' ) );

	// esc_url() returns an empty string if the URL is unacceptable.
	if ( '' === $safe_url ) {
		return '<p>' . esc_html__( 'Invalid website address.', 'website-link-display' ) . '</p>';
	}

	// Build the link. Both the href and the visible text are escaped.
	// rel="nofollow noopener" is good hygiene for user-supplied external links.
	return sprintf(
		'<p><a href="%1$s" rel="nofollow noopener" target="_blank">%2$s</a></p>',
		esc_url( $safe_url ),
		esc_html( $safe_url )
	);
}

/**
 * Shortcode: [website_link]
 * Drop it into any post or page to render the link.
 */
function wld_website_link_shortcode() {
	return wld_render_website_link();
}
add_shortcode( 'website_link', 'wld_website_link_shortcode' );
```

## How to use it

1. Save the code as `website-link-display.php` inside `wp-content/plugins/website-link-display/`.
2. Activate **Website Link Display** in the WordPress admin under *Plugins*.
3. Add the shortcode `[website_link]` to any page or post.
4. Visit that page with the URL parameter, e.g.:
   `https://yoursite.com/my-page/?website_url=https://example.com`

The plugin renders `https://example.com` as a clickable link.

## Why it's written this way (security notes)

- **`esc_url()` with a scheme allow-list** is the key defense. Without it, an input like `?website_url=javascript:alert(document.cookie)` would produce a working XSS payload. Restricting to `http`/`https` blocks `javascript:`, `data:`, etc.
- **`esc_html()` on the visible text** prevents the displayed URL from breaking out of the anchor tag and injecting markup.
- **`wp_unslash()`** undoes WordPress's automatic slashing of request data before validation.
- **`rel="nofollow noopener"`** is hygiene for arbitrary user-supplied external links: it avoids passing SEO trust and closes the `window.opener` tab-nabbing vector that comes with `target="_blank"`.

One thing to be aware of: because the link target comes from an unauthenticated query parameter, this page can be used to craft links that point anywhere on the allowed schemes (an attacker could send someone `?website_url=https://phishing.example`). That's inherent to the feature you asked for — if these links will be shown to other users, consider restricting the host to a known allow-list rather than accepting any URL.

Want me to add a version that takes a list of allowed domains, or one that uses a block instead of a shortcode?
