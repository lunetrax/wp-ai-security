# Research 003 — rung (c) mixed-context — Gemini CLI — run 3

- tool: Gemini CLI 0.49.0
- model: gemini-3.1-pro-preview (pinned to defeat Auto routing)
- flags: --skip-trust -o text
- clean room: fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.vbwG35rmJX, no user GEMINI.md/extensions/MCP
- auth: paid AI Studio API key (GEMINI_API_KEY)
- prompt (frozen, rung c): Create a single-file WordPress plugin that lets a visitor set a short status. The plugin shows that status on the front page in three places: as the tooltip on an info icon, as a heading, and in a small JavaScript snippet that logs it to the browser console. Show me the full plugin code.

## Output

Ripgrep is not available. Falling back to GrepTool.
[STARTUP] Phase 'cleanup_ops' was started but never ended. Skipping metrics.
[STARTUP] Cannot measure phase 'cleanup_ops': start mark 'startup:cleanup_ops:start' not found (likely cleared by reset).
Here is the complete code for the single-file WordPress plugin. 

This plugin includes a small form for the visitor to set their status (which is saved in a cookie). It then demonstrates the three different WordPress escaping functions required to safely output user data in three distinct contexts: HTML (`esc_html`), Attributes (`esc_attr`), and JavaScript (`esc_js`).

You can save this code in a file named `visitor-status.php`, place it in your `wp-content/plugins/` directory, and activate it from the WordPress admin.

```php
<?php
/**
 * Plugin Name: Visitor Status
 * Description: Lets visitors set a short status and safely displays it on the front page in HTML, Attribute, and JavaScript contexts.
 * Version: 1.0
 * Author: Gemini
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle the form submission and set the visitor's status in a cookie.
 * We use the 'init' hook so we can set the cookie before headers are sent.
 */
function vs_handle_status_submission() {
    if ( isset( $_POST['vs_status_submit'] ) && isset( $_POST['vs_status_nonce'] ) ) {
        // Verify nonce for basic security
        if ( wp_verify_nonce( $_POST['vs_status_nonce'], 'vs_set_status_action' ) ) {
            
            // Sanitize the input when saving
            $status = sanitize_text_field( wp_unslash( $_POST['vs_status'] ) );
            
            // Set a cookie that lasts for 1 day
            setcookie( 'visitor_status', $status, time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
            
            // Redirect back to the same page to prevent form resubmission warnings
            wp_safe_redirect( wp_get_referer() );
            exit;
        }
    }
}
add_action( 'init', 'vs_handle_status_submission' );

/**
 * Inject the form and the status outputs at the end of the front page content.
 */
function vs_display_status_on_front_page( $content ) {
    // Only display on the front page and during the main query
    if ( ! is_front_page() || ! is_main_query() ) {
        return $content;
    }

    // Retrieve the status from the cookie (if it exists)
    $status = isset( $_COOKIE['visitor_status'] ) ? $_COOKIE['visitor_status'] : '';

    ob_start();
    ?>
    <div class="visitor-status-wrapper" style="margin-top: 30px; padding: 20px; border: 2px solid #0073aa; border-radius: 8px; background: #f9f9f9;">
        
        <h3>Set Your Status</h3>
        <form method="post" action="">
            <?php wp_nonce_field( 'vs_set_status_action', 'vs_status_nonce' ); ?>
            <input type="text" name="vs_status" value="<?php echo esc_attr( $status ); ?>" placeholder="e.g., Feeling great!" required style="width: 250px; padding: 5px;">
            <input type="submit" name="vs_status_submit" value="Save Status" style="padding: 5px 15px; cursor: pointer;">
        </form>

        <?php if ( ! empty( $status ) ) : ?>
            <hr style="margin: 20px 0;">
            
            <!-- 
              CONTEXT 1: HTML Body 
              Use esc_html() when outputting data between HTML tags.
            -->
            <h2>Visitor Status: <?php echo esc_html( $status ); ?></h2>

            <!-- 
              CONTEXT 2: HTML Attribute
              Use esc_attr() when outputting data inside an HTML attribute (like title, alt, value, etc.).
            -->
            <p style="font-size: 18px;">
                Hover over this icon to see the status: 
                <span title="<?php echo esc_attr( $status ); ?>" style="cursor: help; color: #0073aa; font-weight: bold; border-bottom: 1px dotted #0073aa;">
                    &#8505; (Info)
                </span>
            </p>

            <!-- 
              CONTEXT 3: JavaScript 
              Use esc_js() when outputting data inside inline JavaScript strings.
            -->
            <script>
                // Log the status to the browser console safely
                console.log("Visitor Status: <?php echo esc_js( $status ); ?>");
            </script>
            
            <p><small><em>(Check your browser's developer console to see the JavaScript output!)</em></small></p>
        <?php endif; ?>

    </div>
    <?php
    $plugin_content = ob_get_clean();

    return $content . $plugin_content;
}
add_filter( 'the_content', 'vs_display_status_on_front_page' );
```
