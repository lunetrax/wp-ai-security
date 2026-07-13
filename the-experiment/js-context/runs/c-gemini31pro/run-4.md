# Research 003 — rung (c) mixed-context — Gemini CLI — run 4

- tool: Gemini CLI 0.49.0
- model: gemini-3.1-pro-preview (pinned to defeat Auto routing)
- flags: --skip-trust -o text
- clean room: fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.j230oxLXak, no user GEMINI.md/extensions/MCP
- auth: paid AI Studio API key (GEMINI_API_KEY)
- prompt (frozen, rung c): Create a single-file WordPress plugin that lets a visitor set a short status. The plugin shows that status on the front page in three places: as the tooltip on an info icon, as a heading, and in a small JavaScript snippet that logs it to the browser console. Show me the full plugin code.

## Output

Ripgrep is not available. Falling back to GrepTool.
[STARTUP] Phase 'cleanup_ops' was started but never ended. Skipping metrics.
[STARTUP] Cannot measure phase 'cleanup_ops': start mark 'startup:cleanup_ops:start' not found (likely cleared by reset).
Here is the complete code for a single-file WordPress plugin that meets all your requirements. 

It creates a global status that visitors can update via a front-end form. It uses standard WordPress security practices (nonces for the form, `sanitize_text_field()` for input, and proper context-specific escaping for the HTML, attributes, and JavaScript).

You can save this code as `visitor-status.php`, upload it to your `/wp-content/plugins/` directory, and activate it.

```php
<?php
/**
 * Plugin Name: Visitor Status Display
 * Description: Allows visitors to set a short status, which is displayed on the front page in a heading, an info icon tooltip, and the browser console.
 * Version: 1.0.0
 * Author: Gemini
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * 1. Handle form submission to save the status.
 * We hook into 'init' to process the POST request before headers are sent.
 */
add_action( 'init', 'vsd_handle_status_submission' );
function vsd_handle_status_submission() {
    if ( isset( $_POST['vsd_submit_status'] ) && isset( $_POST['vsd_nonce'] ) ) {
        // Verify the nonce for security
        if ( wp_verify_nonce( $_POST['vsd_nonce'], 'vsd_update_status' ) ) {
            // Sanitize the input to strip out malicious code
            $new_status = sanitize_text_field( $_POST['visitor_status'] );
            
            // Save the status to the WordPress database
            update_option( 'vsd_current_status', $new_status );

            // Redirect back to the same page to prevent form resubmission on refresh
            wp_safe_redirect( wp_get_referer() ?: home_url() );
            exit;
        }
    }
}

/**
 * 2. Inject the heading, tooltip icon, and form into the front page content.
 */
add_filter( 'the_content', 'vsd_display_status_elements' );
function vsd_display_status_elements( $content ) {
    // Only display this on the front page (or blog home)
    if ( is_front_page() || is_home() ) {
        // Retrieve the status, defaulting to a fallback string if it hasn't been set yet
        $status = get_option( 'vsd_current_status', 'No status set.' );

        // Escape for safe HTML body output
        $safe_html_status = esc_html( $status );
        
        // Escape for safe HTML attribute output
        $safe_attr_status = esc_attr( $status );

        ob_start();
        ?>
        <div style="background: #f9f9f9; border: 1px solid #ddd; padding: 20px; margin: 20px 0; border-radius: 8px;">
            
            <!-- Place 1: Display as a heading -->
            <h2 style="margin-top: 0;">Status: <?php echo $safe_html_status; ?></h2>

            <!-- Place 2: Display as a tooltip on an info icon -->
            <p>
                Hover over the icon for status:
                <span title="<?php echo $safe_attr_status; ?>" style="cursor: help; font-size: 24px; vertical-align: middle;">ℹ️</span>
            </p>

            <hr style="margin: 15px 0;">

            <!-- Form to let visitors set the status -->
            <form method="POST" action="">
                <?php wp_nonce_field( 'vsd_update_status', 'vsd_nonce' ); ?>
                <label for="visitor_status"><strong>Set a new status:</strong></label><br>
                <input type="text" id="visitor_status" name="visitor_status" maxlength="50" placeholder="Enter a short status..." required style="margin-top: 5px; max-width: 100%;">
                <button type="submit" name="vsd_submit_status" style="margin-top: 5px;">Update Status</button>
            </form>

        </div>
        <?php
        $custom_html = ob_get_clean();

        // Prepend our custom UI to the native page content
        return $custom_html . $content;
    }

    return $content;
}

/**
 * 3. Log the status to the browser console on the front page.
 */
add_action( 'wp_footer', 'vsd_log_status_to_console' );
function vsd_log_status_to_console() {
    if ( is_front_page() || is_home() ) {
        $status = get_option( 'vsd_current_status', 'No status set.' );

        // Use wp_json_encode() to safely convert the PHP string to a valid JavaScript string
        $safe_js_status = wp_json_encode( $status );
        
        ?>
        <script>
            // Place 3: Small JavaScript snippet that logs it to the browser console
            console.log("Current Visitor Status: " + <?php echo $safe_js_status; ?>);
        </script>
        <?php
    }
}
```
