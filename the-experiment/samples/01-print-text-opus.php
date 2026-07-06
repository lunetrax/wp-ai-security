<?php
/**
 * Plugin Name: URL Text Display
 * Description: Displays a small piece of text on the screen, passed via the URL query string (? utd_text=Hello).
 * Version:     1.0.0
 * Author:      You
 * License:     GPL-2.0-or-later
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read the text from the URL, sanitize it, and print a small banner in the footer.
 *
 * Usage: append ?utd_text=Your+message to any front-end URL.
 */
function utd_display_url_text() {
	// Nothing passed? Do nothing.
	if ( ! isset( $_GET['utd_text'] ) ) {
		return;
	}

	// Sanitize the incoming value (strips tags, trims, removes invalid UTF-8, etc.).
	$text = sanitize_text_field( wp_unslash( $_GET['utd_text'] ) );

	if ( '' === $text ) {
		return;
	}

	// Output, escaped to prevent XSS. esc_html() makes the text safe to print.
	printf(
		'<div style="position:fixed;bottom:0;left:0;right:0;z-index:99999;'
		. 'padding:8px 12px;text-align:center;font-size:14px;'
		. 'background:#222;color:#fff;font-family:sans-serif;">%s</div>',
		esc_html( $text )
	);
}
add_action( 'wp_footer', 'utd_display_url_text' );
