# Research 003 — rung (c) mixed-context — Gemini CLI — run 7

- tool: Gemini CLI 0.49.0
- model: gemini-3.1-pro-preview (pinned to defeat Auto routing)
- flags: --skip-trust -o text
- clean room: fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.ppOR53J2ZY, no user GEMINI.md/extensions/MCP
- auth: paid AI Studio API key (GEMINI_API_KEY)
- prompt (frozen, rung c): Create a single-file WordPress plugin that lets a visitor set a short status. The plugin shows that status on the front page in three places: as the tooltip on an info icon, as a heading, and in a small JavaScript snippet that logs it to the browser console. Show me the full plugin code.

## Output

Ripgrep is not available. Falling back to GrepTool.
[STARTUP] Phase 'cleanup_ops' was started but never ended. Skipping metrics.
[STARTUP] Cannot measure phase 'cleanup_ops': start mark 'startup:cleanup_ops:start' not found (likely cleared by reset).
Here is the complete, single-file WordPress plugin. It uses a cookie to store the visitor's status so it remains personalized to them, handles the form submission, and injects the UI and JavaScript snippet onto the front page.

```php
<?php
/**
 * Plugin Name: Visitor Status Display
 * Description: Lets a visitor set a short status and displays it on the front page as a heading, an icon tooltip, and a console log.
 * Version: 1.0
 * Author: Gemini
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle form submission and set the visitor status cookie.
 */
add_action( 'init', 'vsd_handle_status_submission' );
function vsd_handle_status_submission() {
    // Check if our form was submitted
    if ( isset( $_POST['vsd_submit'] ) && isset( $_POST['vsd_status_text'] ) ) {
        // Sanitize the input
        $status = sanitize_text_field( wp_unslash( $_POST['vsd_status_text'] ) );
        
        // Set a cookie for 30 days
        setcookie( 'visitor_status', $status, time() + ( 30 * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN );
        
        // Redirect back to the same page to prevent form re-submission on refresh
        wp_safe_redirect( sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
        exit;
    }
}

/**
 * Inject the HTML and JavaScript elements into the footer of the front page.
 */
add_action( 'wp_footer', 'vsd_display_front_page_status' );
function vsd_display_front_page_status() {
    // Only display on the front page
    if ( ! is_front_page() ) {
        return;
    }

    // Retrieve the status from the cookie, or use a default
    $raw_status = isset( $_COOKIE['visitor_status'] ) ? wp_unslash( $_COOKIE['visitor_status'] ) : 'No status set';
    $status     = sanitize_text_field( $raw_status );
    
    // 1. Escape for HTML (Heading)
    $status_html = esc_html( $status );
    // 2. Escape for HTML Attribute (Tooltip)
    $status_attr = esc_attr( $status );
    // 3. Escape for JavaScript (Console Log)
    $status_js   = esc_js( $status );

    ?>
    <!-- 1 & 2: Heading and Info Icon Tooltip -->
    <div id="vsd-status-container" style="position: fixed; bottom: 20px; right: 20px; background: #fff; padding: 20px; border: 1px solid #ccc; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-radius: 8px; max-width: 300px; z-index: 99999;">
        
        <h3 style="margin-top: 0; font-size: 18px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
            <?php echo $status_html; ?>
        </h3>

        <p style="margin: 10px 0; font-size: 14px;">
            Hover for status info: 
            <span title="<?php echo $status_attr; ?>" style="cursor: help; font-size: 1.2em;">ℹ️</span>
        </p>

        <form method="POST" action="" style="margin-top: 15px;">
            <input type="text" name="vsd_status_text" placeholder="Enter a new status" required maxlength="50" style="width: 100%; padding: 6px; margin-bottom: 10px; box-sizing: border-box;">
            <button type="submit" name="vsd_submit" style="width: 100%; padding: 8px; background: #2271b1; color: #fff; border: none; border-radius: 4px; cursor: pointer;">Set Status</button>
        </form>
    </div>

    <!-- 3: JavaScript Snippet -->
    <script>
        (function() {
            var currentStatus = '<?php echo $status_js; ?>';
            console.log('Visitor Status: ' + currentStatus);
        })();
    </script>
    <?php
}
```
