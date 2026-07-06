I wasn't able to write the file (permission not granted), so here's the full plugin code to copy into `wp-content/plugins/website-link-display/website-link-display.php`:

```php
<?php
/**
 * Plugin Name: Website Link Display
 * Description: Reads a website address from a query parameter and renders it as a safe, clickable link via the [website_link] shortcode.
 * Version:     1.0.0
 * Author:      Your Name
 * License:     GPL-2.0-or-later
 */

// Block direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validate and normalize a URL coming from untrusted input.
 *
 * Only http/https are allowed, which prevents javascript:, data:, and
 * other dangerous schemes from being turned into a clickable link.
 *
 * @param string $raw Raw value from the query parameter.
 * @return string|false Clean URL, or false if it is not acceptable.
 */
function wld_clean_url( $raw ) {
	$raw = wp_unslash( $raw );

	// Reject anything that is not a string with content.
	if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
		return false;
	}

	// Restrict to safe, web-facing protocols only.
	$url = esc_url_raw( trim( $raw ), array( 'http', 'https' ) );

	if ( empty( $url ) ) {
		return false;
	}

	// Final structural sanity check.
	if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
		return false;
	}

	return $url;
}

/**
 * Shortcode: [website_link param="website" text="Visit site"]
 *
 * Outputs a clickable link built from the requested query parameter.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML for the link, or a fallback message.
 */
function wld_render_link( $atts ) {
	$atts = shortcode_atts(
		array(
			'param' => 'website', // Query parameter to read, e.g. ?website=...
			'text'  => '',         // Optional custom link text.
		),
		$atts,
		'website_link'
	);

	$key = sanitize_key( $atts['param'] );

	// Nothing provided.
	if ( ! isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return '<p class="wld-empty">No website address was provided.</p>';
	}

	$url = wld_clean_url( $_GET[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( false === $url ) {
		return '<p class="wld-error">The provided website address is not a valid http(s) URL.</p>';
	}

	// Link text: use the attribute if given, otherwise show the URL itself.
	$label = '' !== $atts['text'] ? $atts['text'] : $url;

	return sprintf(
		'<a class="wld-link" href="%1$s" rel="nofollow noopener noreferrer" target="_blank">%2$s</a>',
		esc_url( $url ),
		esc_html( $label )
	);
}

add_action(
	'init',
	function () {
		add_shortcode( 'website_link', 'wld_render_link' );
	}
);
```

## How to use it

1. Save the code as `website-link-display.php` inside `wp-content/plugins/` (a subfolder is fine too).
2. Activate **Website Link Display** in **Plugins**.
3. Put the shortcode on any page or post:
   - `[website_link]` — reads `?website=...`
   - `[website_link param="url" text="Go to site"]` — reads `?url=...` and uses custom link text.
4. Visit the page with the parameter, e.g. `https://example.com/my-page/?website=https://wordpress.org`.

## Why it's written this way (security notes)

Echoing a URL straight from `$_GET` into an `<a href>` is the textbook reflected-XSS / open-redirect bug. This version defends against it:

- **Scheme allow-list** — `esc_url_raw($url, array('http','https'))` rejects `javascript:`, `data:`, `vbscript:`, etc., so the link can never execute script.
- **Output escaping** — `esc_url()` on the `href` and `esc_html()` on the visible text neutralize any injected markup or quotes.
- **Validation** — `filter_var(... FILTER_VALIDATE_URL)` discards malformed input; the user sees a clear message instead of a broken/dangerous link.
- **`rel="nofollow noopener noreferrer"`** — avoids passing SEO equity and `window.opener` access to arbitrary attacker-controlled destinations.

One thing worth deciding for your case: this intentionally only accepts absolute `http(s)` URLs. If you also want to allow site-relative paths, that needs extra handling — tell me and I'll adjust.

If you'd rather it render automatically without a shortcode (e.g. appended to content or shown in a widget/template), I can adapt it.
