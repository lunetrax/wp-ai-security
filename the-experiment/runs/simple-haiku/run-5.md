I'll create a simple WordPress plugin that displays text passed via URL parameter. Here's the complete single-file plugin:

```php
<?php
/**
 * Plugin Name: Display Text from URL
 * Description: Displays text passed through the URL parameter
 * Version: 1.0
 * Author: Your Name
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_footer', 'dtu_display_text_from_url' );

function dtu_display_text_from_url() {
    if ( isset( $_GET['message'] ) ) {
        $text = sanitize_text_field( wp_unslash( $_GET['message'] ) );
        
        echo '<div style="padding: 15px; background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; margin: 20px; font-family: Arial, sans-serif;">';
        echo esc_html( $text );
        echo '</div>';
    }
}
?>
```

**How to use:**

1. Create a file named `display-url-text.php`
2. Add the code above to the file
3. Upload it to `/wp-content/plugins/` directory
4. Activate the plugin in WordPress admin
5. Use it by adding the parameter to any URL: `https://yoursite.com/any-page/?message=Hello%20World`

The text will display in a styled box at the bottom of the page (in the footer). The plugin:
- ✅ Safely sanitizes and escapes the input to prevent XSS attacks
- ✅ Uses proper WordPress hooks (`wp_footer`)
- ✅ Includes required plugin headers
- ✅ Adds a security check for `ABSPATH`

You can customize the styling by modifying the `style` attribute in the `div` tag.
