I'll create a single-file WordPress plugin with a shortcode that displays text from a URL parameter:

```php
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
```

## How to use:

1. **Install the plugin:**
   - Save this code to a file like `url-text-display.php`
   - Upload to `/wp-content/plugins/` and activate it in WordPress

2. **Add the shortcode to a page or post:**
   - Edit any page/post and add: `[display_text]`
   - Save and publish

3. **Display text via URL:**
   - Visit the page with a query parameter: `https://yoursite.com/your-page/?text=Hello%20World`
   - The text "Hello World" will display on the page

The plugin includes security best practices:
- **`sanitize_text_field()`** – Removes harmful code from the URL input
- **`esc_html()`** – Escapes the output to prevent XSS attacks
- **`ABSPATH` check** – Prevents direct file access
