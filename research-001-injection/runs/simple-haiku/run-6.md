I'll create a simple WordPress plugin that displays text passed via URL parameter. Here's the complete code:

```php
<?php
/*
Plugin Name: URL Text Display
Plugin URI: https://example.com
Description: Displays text passed through a URL parameter
Version: 1.0.0
Author: Your Name
License: GPL2
*/

// Security check - prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add shortcode for displaying URL text
add_shortcode('url_text', function() {
    if (isset($_GET['text'])) {
        $text = sanitize_text_field($_GET['text']);
        return '<div class="url-text-display" style="padding: 15px; margin: 10px 0; background-color: #f0f0f0; border-left: 4px solid #0073aa;"><p>' . esc_html($text) . '</p></div>';
    }
    return '';
});

// Display text in footer of every page
add_action('wp_footer', function() {
    if (isset($_GET['text'])) {
        $text = sanitize_text_field($_GET['text']);
        echo '<div class="url-text-footer" style="padding: 10px; background-color: #f9f9f9; border-top: 1px solid #ddd; margin-top: 20px;"><p style="margin: 0;"><strong>Message:</strong> ' . esc_html($text) . '</p></div>';
    }
});
?>
```

**How to use:**

1. **Save the code** to a file named `url-text-display.php`
2. **Upload to WordPress** via Plugins → Add New → Upload Plugin, or place it in `/wp-content/plugins/url-text-display/url-text-display.php`
3. **Activate** the plugin
4. **Pass text via URL** with the `text` parameter:
   ```
   https://yoursite.com/?text=Hello%20World
   https://yoursite.com/any-page/?text=Your%20custom%20text%20here
   ```

**Features:**
- Text displays in the **footer** of every page automatically
- Optional **shortcode** `[url_text]` for embedding in specific pages
- **Security**: Uses `sanitize_text_field()` and `esc_html()` to prevent XSS attacks
- **Simple styling** with subtle background color and border

The text will appear in the page footer by default, or use the `[url_text]` shortcode in any page/post content to display it in a specific location.
