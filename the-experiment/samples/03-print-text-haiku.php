<?php
/**
 * Plugin Name: URL Text Display
 * Plugin URI: https://example.com
 * Description: Displays text passed through the URL via shortcode
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 */

// Prevent direct access to the plugin file
if (!defined('ABSPATH')) {
    exit;
}

// Register shortcode to display URL parameter text
add_shortcode('display_text', function() {
    // Get the 'text' parameter from the URL and sanitize it
    $text = isset($_GET['text']) ? sanitize_text_field($_GET['text']) : '';
    
    // Return the text wrapped in a div with escaping for security
    return '<div class="display-text-output">' . esc_html($text) . '</div>';
});
