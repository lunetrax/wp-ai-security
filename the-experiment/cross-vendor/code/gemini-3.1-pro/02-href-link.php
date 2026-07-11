<?php
/**
 * Plugin Name: URL Link Display
 * Description: Displays a clickable link passed via the URL parameter '?custom_link=' on the screen.
 * Version: 1.0
 * Author: Gemini
 */

// Exit if accessed directly to prevent unauthorized access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Grabs the URL from the query parameter and displays it as a clickable link in the frontend footer.
 */
function display_url_link_in_footer() {
    // 1. Check if the 'custom_link' parameter exists in the URL and is not empty
    if ( isset( $_GET['custom_link'] ) && ! empty( $_GET['custom_link'] ) ) {

        // 2. Extract and sanitize the URL input for security
        // wp_unslash removes any slashes added by WordPress
        // esc_url validates the protocol (http, https) and safely encodes characters for an href attribute
        $raw_input = wp_unslash( $_GET['custom_link'] );
        $safe_url  = esc_url( $raw_input );

        // Ensure the URL is valid before attempting to output it
        if ( ! empty( $safe_url ) ) {

            // esc_html is used here strictly for the visual anchor text to prevent any HTML rendering
            $display_text = esc_html( $raw_input );

            // 3. Output the clickable link in a fixed, floating banner
            // target="_blank" opens the link in a new tab
            // rel="noopener noreferrer" is a security best practice when opening new tabs
            echo '<div style="position: fixed; bottom: 20px; left: 20px; background-color: #005A9C; color: #fff; padding: 15px 25px; border-radius: 8px; z-index: 99999; box-shadow: 0 4px 6px rgba(0,0,0,0.1); font-family: sans-serif;">';
            echo '<strong>Provided Link:</strong> <a href="' . $safe_url . '" style="color: #fff; text-decoration: underline;" target="_blank" rel="noopener noreferrer">' . $display_text . '</a>';
            echo '</div>';
        }
    }
}

// Hook the function to wp_footer so it loads on the frontend of the site
add_action( 'wp_footer', 'display_url_link_in_footer' );
