<?php
/**
 * Plugin Name: Visitor Status
 * Description: Lets visitors set a short status and securely displays it on the front page in multiple contexts.
 * Version: 1.0
 * Author: Gemini
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * 1. Handle form submission
 * Intercept POST request to save the user's status globally.
 */
add_action( 'init', 'vs_handle_status_submission' );
function vs_handle_status_submission() {
	if ( isset( $_POST['vs_submit'], $_POST['vs_status'], $_POST['vs_nonce'] ) ) {
		// Verify the nonce for basic CSRF protection
		if ( wp_verify_nonce( $_POST['vs_nonce'], 'vs_update_status' ) ) {
			// Unslash and sanitize the text input before saving to the database
			$status = sanitize_text_field( wp_unslash( $_POST['vs_status'] ) );
			update_option( 'vs_visitor_status', $status );
		}
	}
}

/**
 * 2. Display the form, heading, and tooltip on the front page
 * Hooks into 'the_content' to inject our HTML.
 */
add_filter( 'the_content', 'vs_display_status_content' );
function vs_display_status_content( $content ) {
	// Only alter the main query on the front page
	if ( is_front_page() && in_the_loop() && is_main_query() ) {
		// Retrieve the status from the database
		$raw_status = get_option( 'vs_visitor_status', 'No status set yet.' );

		// CONTEXT 1: HTML Body. We use esc_html() to prevent HTML injection.
		$heading = '<h2>' . esc_html( $raw_status ) . '</h2>';

		// CONTEXT 2: HTML Attribute. We use esc_attr() to safely insert data inside the title="".
		$icon = '<span title="' . esc_attr( $raw_status ) . '" style="cursor:help; font-size:24px;">ℹ️</span>';

		// Simple form for visitors to set the new status
		$form = '
		<div style="background:#f9f9f9; padding:15px; margin-bottom:20px; border: 1px solid #ddd; border-radius:4px;">
			<form method="POST" action="">
				' . wp_nonce_field( 'vs_update_status', 'vs_nonce', true, false ) . '
				<label for="vs_status" style="display:block; margin-bottom:8px;"><strong>Set a new status:</strong></label>
				<input type="text" name="vs_status" id="vs_status" placeholder="Type something..." required />
				<button type="submit" name="vs_submit">Update Status</button>
			</form>
		</div>';

		// Combine our elements with the original page content
		$injected_html = $form . $heading . '<p>Hover over this icon to see the status tooltip: ' . $icon . '</p><hr>';

		return $injected_html . $content;
	}

	return $content;
}

/**
 * 3. Display the JavaScript snippet in the footer
 * Hooks into 'wp_footer' to log the status to the console.
 */
add_action( 'wp_footer', 'vs_display_status_js' );
function vs_display_status_js() {
	// Only execute on the front page
	if ( is_front_page() ) {
		// Retrieve the status from the database
		$raw_status = get_option( 'vs_visitor_status', 'No status set yet.' );

		// CONTEXT 3: JavaScript. We use esc_js() to safely insert data into inline JS.
		// (This escapes quotes and prevents breaking out of the console.log string).
		$safe_js_status = esc_js( $raw_status );

		echo "<script>
			console.log('Current Visitor Status: {$safe_js_status}');
		</script>";
	}
}
