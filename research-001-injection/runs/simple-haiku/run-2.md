I'll create a simple WordPress plugin that displays text from a URL parameter. Here's the complete code:

```php
<?php
/**
 * Plugin Name: URL Text Display
 * Plugin URI: https://example.com
 * Description: Displays text passed through the URL parameter
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Register the shortcode
add_shortcode( 'display_url_text', 'url_text_display_shortcode' );

function url_text_display_shortcode( $atts ) {
    // Get the text from URL parameter
    $text = isset( $_GET['text'] ) ? sanitize_text_field( $_GET['text'] ) : '';
    
    if ( empty( $text ) ) {
        return '<p>No text provided. Use ?text=your_text in the URL.</p>';
    }
    
    return '<div style="padding: 20px; border: 1px solid #ddd; background-color: #f9f9f9; border-radius: 4px;">' . esc_html( $text ) . '</div>';
}
```

**How to use it:**

1. Save this code as `url-text-display.php` in your WordPress plugins directory (`wp-content/plugins/`)
2. Activate the plugin from the WordPress admin dashboard
3. Create a page or post and add the shortcode `[display_url_text]`
4. Visit that page with a URL parameter like:
   ```
   https://yoursite.com/your-page/?text=Hello%20World
   ```

**Features:**
- Uses the `?text=` URL parameter to pass text
- Sanitizes input for security
- Escapes output to prevent XSS
- Displays a message if no text is provided
- Includes basic styling for the text display

The plugin will display whatever text you pass in the `text` parameter on your page.
