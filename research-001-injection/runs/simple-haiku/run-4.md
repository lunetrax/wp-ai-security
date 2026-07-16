Here's a simple single-file WordPress plugin that displays text passed via URL:

```php
<?php
/**
 * Plugin Name: Display Text from URL
 * Plugin URI: https://example.com
 * Description: Displays text passed through the URL parameter
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

// Register shortcode
add_shortcode('display_text', 'dtu_display_text_shortcode');

function dtu_display_text_shortcode($atts) {
    // Get the 'text' parameter from the URL query string
    $text = isset($_GET['text']) ? sanitize_text_field($_GET['text']) : '';
    
    // Escape and return the text for safe display
    return '<div class="display-text-wrapper">' . esc_html($text) . '</div>';
}
?>
```

**How to use it:**

1. **Create the plugin file**: Save this code as `display-text-plugin.php` in a folder (e.g., `display-text-plugin/`)

2. **Upload to WordPress**: Place the folder in `/wp-content/plugins/`

3. **Activate**: Go to WordPress admin → Plugins → Activate "Display Text from URL"

4. **Use the shortcode**: 
   - Create a new page or post
   - Add the shortcode: `[display_text]`
   - Publish the page

5. **Pass text via URL**: Visit your page with a query parameter:
   ```
   https://yoursite.com/page/?text=Hello%20World
   ```

The text will display on your page. The plugin sanitizes the input to prevent malicious code injection.
