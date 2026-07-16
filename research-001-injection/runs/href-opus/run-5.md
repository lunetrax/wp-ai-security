Here's the complete single-file plugin. Save it as `website-link-display.php` in `wp-content/plugins/`.

```php
<?php
/**
 * Plugin Name:       Website Link Display
 * Description:       Displays a website address taken from a query parameter as a safe, clickable link via the [website_link] shortcode.
 * Version:           1.0.0
 * Author:            Your Name
 * License:           GPL-2.0-or-later
 * Text Domain:       website-link-display
 *
 * Usage: place [website_link] in any post or page, then visit the page with
 *        ?website=https://example.com appended to the URL.
 */

// Block direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode handler: read the "website" query parameter and render it as a link.
 *
 * @param array $atts Shortcode attributes.
 *                    - param: query var name to read (default "website").
 *                    - text:  optional link text; defaults to the URL itself.
 * @return string Safe HTML for the link, or an empty string if no valid URL.
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

	// Sanitize the query var name itself, then read the raw value.
	$param = sanitize_key( $atts['param'] );

	if ( ! isset( $_GET[ $param ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only, public display.
		return '';
	}

	$raw_url = wp_unslash( $_GET[ $param ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	// Reject anything that is not a string (e.g. ?website[]=...).
	if ( ! is_string( $raw_url ) ) {
		return '';
	}

	// First pass: strip out invalid URL characters, restrict to http/https.
	$url = esc_url_raw( $raw_url, array( 'http', 'https' ) );

	if ( empty( $url ) ) {
		return '';
	}

	// Defense in depth: require a valid http/https URL with a host.
	if ( ! wp_http_validate_url( $url ) ) {
		return '';
	}

	// Link text: use provided text, otherwise the URL. Never trust either as HTML.
	$text = '' !== $atts['text'] ? $atts['text'] : $url;

	return sprintf(
		'<a href="%s" rel="nofollow noopener noreferrer">%s</a>',
		esc_url( $url ),            // Escapes for the href attribute; blocks javascript: etc.
		esc_html( $text )           // Escapes for HTML output; blocks injected markup.
	);
}
add_shortcode( 'website_link', 'wld_render_website_link' );
```

## How to use it

1. Drop the file into `wp-content/plugins/website-link-display.php` and activate it in **Plugins**.
2. Add the shortcode `[website_link]` to any post or page.
3. Visit that page with the URL parameter, e.g.
   `https://yoursite.com/my-page/?website=https://example.com`

The page renders a clickable link to `https://example.com`. Optional attributes: `[website_link param="url" text="Visit site"]`.

## Why the validation matters (don't skip it)

Echoing a query parameter straight into an `<a href>` is one of the most common WordPress vulnerabilities. The plugin defends against it on three fronts:

- **`esc_url` / `esc_url_raw` with a protocol whitelist** — blocks `javascript:`, `data:`, and `vbscript:` URIs, so an attacker can't turn the link into a script payload like `?website=javascript:alert(document.cookie)`.
- **`esc_html` on the link text** — prevents reflected XSS where the parameter breaks out of the attribute and injects markup.
- **`wp_http_validate_url` + array-type check** — ensures the value is a single, well-formed http(s) URL with a host, not an array or garbage.
- **`rel="nofollow noopener noreferrer"`** — since the destination is attacker-controllable, this avoids passing SEO value and protects against reverse-tabnabbing.

One thing to keep in mind even with this hardening: because *anyone* can craft a link to your page with any `?website=` value, the displayed link points wherever the visitor's URL says. That's fine for a "preview the link you pasted" feature, but don't use this pattern to imply your site endorses the destination, and don't store/echo the value elsewhere without re-escaping for that context.

I tried to write the file to your working directory but the write permission wasn't granted — let me know if you'd like me to save it there.
