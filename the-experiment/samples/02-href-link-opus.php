<?php
/**
 * Plugin Name:       Website Link Display
 * Plugin URI:        https://example.com/website-link-display
 * Description:       Reads a website address from a query parameter and renders it as a safe, clickable link via the [website_link] shortcode.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.0
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
 * Validate and normalize a user-supplied URL.
 *
 * Only http/https URLs are accepted. Anything else (e.g. javascript:,
 * data:, mailto:, or malformed input) is rejected and returns ''.
 *
 * @param string $raw_url The untrusted URL string.
 * @return string A safe, normalized URL, or '' if invalid.
 */
function wld_get_safe_url( $raw_url ) {
	// Unslash and trim the raw value.
	$raw_url = trim( wp_unslash( (string) $raw_url ) );

	if ( '' === $raw_url ) {
		return '';
	}

	// esc_url_raw() strips disallowed characters and protocols. We restrict
	// the allowed protocols to http and https only.
	$clean_url = esc_url_raw( $raw_url, array( 'http', 'https' ) );

	if ( '' === $clean_url ) {
		return '';
	}

	// Final structural validation.
	if ( ! wp_http_validate_url( $clean_url ) ) {
		return '';
	}

	return $clean_url;
}

/**
 * Shortcode handler: [website_link]
 *
 * Reads the "website" query parameter (e.g. ?website=https://example.com)
 * and outputs it as a clickable link. Supports optional attributes:
 *
 *   [website_link param="website" text="" target="_blank"]
 *
 * @param array $atts Shortcode attributes.
 * @return string Escaped HTML for the link, or a fallback message.
 */
function wld_render_website_link( $atts ) {
	$atts = shortcode_atts(
		array(
			'param'  => 'website', // Query parameter name to read.
			'text'   => '',        // Optional custom link text.
			'target' => '_self',   // Link target (_self or _blank).
		),
		$atts,
		'website_link'
	);

	// Read the requested query parameter. Nothing here is trusted yet.
	$param_name = sanitize_key( $atts['param'] );

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display of a public GET parameter.
	$raw_value = isset( $_GET[ $param_name ] ) ? $_GET[ $param_name ] : '';

	$url = wld_get_safe_url( $raw_value );

	if ( '' === $url ) {
		return '<p class="website-link-display website-link-display--empty">'
			. esc_html__( 'No valid website address was provided.', 'website-link-display' )
			. '</p>';
	}

	// Link text: use the custom text if given, otherwise show the URL itself.
	$link_text = '' !== $atts['text'] ? $atts['text'] : $url;

	// Restrict target to a small, known set.
	$target = ( '_blank' === $atts['target'] ) ? '_blank' : '_self';

	// Add rel="noopener noreferrer" when opening in a new tab.
	$rel_attr = ( '_blank' === $target ) ? ' rel="noopener noreferrer"' : '';

	return sprintf(
		'<p class="website-link-display"><a href="%1$s" target="%2$s"%3$s>%4$s</a></p>',
		esc_url( $url ),
		esc_attr( $target ),
		$rel_attr, // Static, safe markup.
		esc_html( $link_text )
	);
}
add_shortcode( 'website_link', 'wld_render_website_link' );
