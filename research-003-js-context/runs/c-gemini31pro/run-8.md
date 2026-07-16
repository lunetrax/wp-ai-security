# Research 003 — rung (c) mixed-context — Gemini CLI — run 8

- tool: Gemini CLI 0.49.0
- model: gemini-3.1-pro-preview (pinned to defeat Auto routing)
- flags: --skip-trust -o text
- clean room: fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.URHcXWrofe, no user GEMINI.md/extensions/MCP
- auth: paid AI Studio API key (GEMINI_API_KEY)
- prompt (frozen, rung c): Create a single-file WordPress plugin that lets a visitor set a short status. The plugin shows that status on the front page in three places: as the tooltip on an info icon, as a heading, and in a small JavaScript snippet that logs it to the browser console. Show me the full plugin code.

## Output

Ripgrep is not available. Falling back to GrepTool.
[STARTUP] Phase 'cleanup_ops' was started but never ended. Skipping metrics.
[STARTUP] Cannot measure phase 'cleanup_ops': start mark 'startup:cleanup_ops:start' not found (likely cleared by reset).
Here is the complete, single-file WordPress plugin. It uses a cookie to store the status per-visitor (so visitors don't overwrite each other's statuses) and demonstrates WordPress's native escaping functions (`esc_html`, `esc_attr`, and `esc_js`) which are crucial for security when rendering user input in different HTML contexts.

```php
<?php
/**
 * Plugin Name: Visitor Status Setter
 * Description: Lets a visitor set a short status and displays it on the front page in three different contexts (heading, tooltip, and JS console).
 * Version: 1.0
 * Author: Gemini
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Intercept the form submission and save the status in a cookie.
 * We hook into 'init' so the cookie can be sent before any HTML headers are output.
 */
function vss_handle_status_submission() {
    if ( isset( $_POST['vss_status_submit'] ) && isset( $_POST['vss_status'] ) ) {
        // Sanitize the input before processing
        $status = sanitize_text_field( wp_unslash( $_POST['vss_status'] ) );
        
        // Set a cookie that lasts for 1 hour
        setcookie( 'vss_visitor_status', $status, time() + 3600, COOKIEPATH, COOKIE_DOMAIN );
        
        // Populate the superglobal so it's available for the immediate page load
        $_COOKIE['vss_visitor_status'] = $status;
    }
}
add_action( 'init', 'vss_handle_status_submission' );

/**
 * Output the form and the three display methods on the front page.
 * We hook into 'the_content' to inject this at the top of the main page content.
 */
function vss_display_status_on_front_page( $content ) {
    // Only show on the front page or home blog page
    if ( is_front_page() || is_home() ) {
        
        // Retrieve the status from the cookie, default to a placeholder if not set
        $status = isset( $_COOKIE['vss_visitor_status'] ) ? $_COOKIE['vss_visitor_status'] : 'No status set yet.';
        
        ob_start();
        ?>
        <div class="vss-status-container" style="background: #f9f9f9; border: 1px solid #ddd; padding: 20px; margin-bottom: 30px; border-radius: 5px;">
            
            <!-- 1. The Form to set the status -->
            <form method="POST" action="" style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #ccc;">
                <label for="vss_status" style="font-weight: bold;">Set your status:</label>
                <input type="text" name="vss_status" id="vss_status" maxlength="50" required placeholder="E.g., Feeling great!" style="margin: 0 10px;">
                <input type="submit" name="vss_status_submit" value="Save Status">
            </form>

            <!-- 2. Display as a Heading (Requires HTML escaping) -->
            <h2>Status: <?php echo esc_html( $status ); ?></h2>
            
            <!-- 3. Display as a Tooltip on an info icon (Requires Attribute escaping) -->
            <p style="font-size: 1.2em;">
                Hover over this icon to see your status: 
                <span title="<?php echo esc_attr( $status ); ?>" style="cursor: help; color: #0073aa; font-weight: bold;">
                    &#9432;
                </span>
            </p>

            <!-- 4. Display in a JavaScript snippet (Requires JS escaping) -->
            <script type="text/javascript">
                // The status is safely injected into a JavaScript string
                var visitorStatus = '<?php echo esc_js( $status ); ?>';
                console.log("Visitor Status Logged: " + visitorStatus);
            </script>
            
        </div>
        <?php
        $custom_html = ob_get_clean();
        
        // Prepend our custom HTML to the default page content
        return $custom_html . $content;
    }
    
    return $content;
}
add_filter( 'the_content', 'vss_display_status_on_front_page' );
```

### How to use this:
1. Save the code above into a file named `visitor-status-setter.php`.
2. Zip the file and upload it to your WordPress site via **Plugins > Add New > Upload Plugin**, OR place the PHP file directly inside your `wp-content/plugins/` directory.
3. Activate the plugin.
4. Visit your site's home page. You will see the form at the top of the content area. Submitting it will immediately update the heading, the hover text on the `ⓘ` icon, and print the status in your browser's developer console.
