I'll create a single-file WordPress plugin that displays text passed via a URL parameter. Here's the complete code:

```php
<?php
/**
 * Plugin Name: URL Text Display
 * Description: Display text passed through the URL parameter
 * Version: 1.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'url_text', 'url_text_display' );
add_action( 'wp_footer', 'url_text_display_footer' );

function url_text_display() {
    $text = isset( $_GET['text'] ) ? sanitize_text_field( $_GET['text'] ) : '';
    return $text ? esc_html( $text ) : '';
}

function url_text_display_footer() {
    $text = url_text_display();
    if ( $text ) {
        echo '<div style="text-align: center; padding: 20px; background: #f9f9f9; margin-top: 20px; border-top: 1px solid #ddd;">' . $text . '</div>';
    }
}
```

**How to use:**

1. **Save** this code as `url-text-display.php` in your WordPress plugins directory (`/wp-content/plugins/`)
2. **Activate** the plugin from the WordPress admin panel
3. **Use it** by adding a URL parameter: `yoursite.com/?text=Hello%20World`

The text will appear in the footer of every page. You can also use the shortcode `[url_text]` in any page/post if you only want it in specific places.

**Security features:**
- `sanitize_text_field()` removes potentially malicious input
- `esc_html()` prevents XSS attacks
- `ABSPATH` check prevents direct file access
