<?php
/**
 * Plugin Name: URL Text Display
 * Description: Displays text passed via the URL parameter '?custom_text=' on the screen.
 * Version: 1.0
 * Author: Gemini
 */

// Exit if accessed directly to prevent unauthorized access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Grabs the text from the URL and displays it on the frontend.
 */
function display_url_text_in_footer() {
    // 1. Check if the 'custom_text' parameter exists in the URL and is not empty
    if ( isset( $_GET['custom_text'] ) && ! empty( $_GET['custom_text'] ) ) {

        // 2. Sanitize and escape the URL input for security
        // wp_unslash removes any slashes added by WordPress
        // sanitize_text_field strips out HTML tags
        // esc_html ensures any remaining special characters are safely rendered
        $safe_text = esc_html( sanitize_text_field( wp_unslash( $_GET['custom_text'] ) ) );

        // 3. Output the text in a fixed, floating banner so it is easily visible
        echo '<div style="position: fixed; bottom: 20px; right: 20px; background-color: #333; color: #fff; padding: 15px 25px; border-radius: 8px; z-index: 99999; box-shadow: 0 4px 6px rgba(0,0,0,0.1); font-family: sans-serif;">';
        echo '<strong>URL Text:</strong> ' . $safe_text;
        echo '</div>';
    }
}

// Hook the function to wp_footer so it loads on the frontend of the site
add_action( 'wp_footer', 'display_url_text_in_footer' );
