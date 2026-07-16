Here's the complete WordPress plugin code:

```php
<?php
/**
 * Plugin Name: URL Text Display
 * Description: Displays text passed through URL parameter
 * Version: 1.0
 * Author: Your Name
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Shortcode to display text from URL parameter
add_shortcode( 'display_text', 'url_text_display_shortcode' );

function url_text_display_shortcode() {
    if ( isset( $_GET['text'] ) ) {
        $text = sanitize_text_field( wp_unslash( $_GET['text'] ) );
        return '<div class="url-text-display">' . esc_html( $text ) . '</div>';
    }
    return '';
}
```

**How to use:**

1. **Install**: Save this as `url-text-display.php` in your WordPress plugins folder (`/wp-content/plugins/`)
2. **Activate**: Go to WordPress admin → Plugins and activate "URL Text Display"
3. **Add to a page**: Place the shortcode `[display_text]` on any page or post
4. **Pass text via URL**: Visit the page with `?text=Hello%20World` appended to the URL

Example: `https://yoursite.com/my-page/?text=Hello%20World` will display "Hello World" on the page.

The plugin:
- Sanitizes and escapes the URL parameter to prevent XSS attacks
- Displays the text only when the `text` parameter is present
- Returns nothing if no parameter is provided
