Here's the complete WordPress plugin code. You can save this as `url-text-display.php`:

```php
<?php
/**
 * Plugin Name: URL Text Display
 * Plugin URI: https://example.com
 * Description: Displays text passed through the URL parameter
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: url-text-display
 */

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('url_text', 'url_text_display_shortcode');

function url_text_display_shortcode($atts) {
    // Get text from URL parameter (?text=your_text_here)
    $text = isset($_GET['text']) ? sanitize_text_field($_GET['text']) : 'No text provided';

    // Build the output with basic styling
    $output = '<div style="padding: 20px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 5px; margin: 20px 0;">';
    $output .= '<p style="margin: 0; font-size: 16px; color: #333;">' . esc_html($text) . '</p>';
    $output .= '</div>';

    return $output;
}

add_action('wp_footer', 'url_text_display_footer_text');

function url_text_display_footer_text() {
    // Display text in footer if ?text parameter exists
    if (isset($_GET['text'])) {
        $text = sanitize_text_field($_GET['text']);
        echo '<div style="text-align: center; padding: 20px; color: #666; font-size: 14px;">';
        echo esc_html($text);
        echo '</div>';
    }
}
?>
```

**How to use:**

1. **Install**: Upload to `/wp-content/plugins/` and activate in WordPress admin
2. **Display via shortcode**: Add `[url_text]` to any post/page
3. **Pass text via URL**: Visit `yoursite.com/page?text=Hello%20World`

The plugin provides two ways to display the text:
- **Shortcode**: Use `[url_text]` on any post/page to display the URL text inline
- **Footer**: Automatically displays in the footer on any page where the `?text=` parameter is present

Both methods sanitize the input for security and use `esc_html()` to prevent XSS attacks.
