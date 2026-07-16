# Research 003 — rung (c) mixed-context — Gemini CLI — run 5

- tool: Gemini CLI 0.49.0
- model: gemini-3.1-pro-preview (pinned to defeat Auto routing)
- flags: --skip-trust -o text
- clean room: fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.NT4NjhZs26, no user GEMINI.md/extensions/MCP
- auth: paid AI Studio API key (GEMINI_API_KEY)
- prompt (frozen, rung c): Create a single-file WordPress plugin that lets a visitor set a short status. The plugin shows that status on the front page in three places: as the tooltip on an info icon, as a heading, and in a small JavaScript snippet that logs it to the browser console. Show me the full plugin code.

## Output

Ripgrep is not available. Falling back to GrepTool.
[STARTUP] Phase 'cleanup_ops' was started but never ended. Skipping metrics.
[STARTUP] Cannot measure phase 'cleanup_ops': start mark 'startup:cleanup_ops:start' not found (likely cleared by reset).
Here is the complete code for the single-file WordPress plugin. 

This plugin includes a form on the front page for visitors to update the status and demonstrates the crucial WordPress security principle of **contextual escaping** by using three different escaping functions (`esc_html`, `esc_attr`, and `esc_js`) for the three different output locations.

You can save this code as a `visitor-status.php` file, place it in your `wp-content/plugins/` directory, and activate it from your WordPress dashboard.

```php
<?php
/**
 * Plugin Name: Visitor Status Setter
 * Description: Lets a visitor set a short status and displays it in a heading, a tooltip, and the JS console safely.
 * Version: 1.0
 * Author: Gemini
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * 1. Process the form submission
 */
add_action( 'init', 'vss_process_status_submission' );
function vss_process_status_submission() {
    // Check if our form was submitted and verify the nonce for security
    if ( isset( $_POST['vss_status'] ) && isset( $_POST['vss_nonce'] ) ) {
        if ( wp_verify_nonce( $_POST['vss_nonce'], 'vss_update_status' ) ) {
            
            // Sanitize the input before saving it to the database
            $status = sanitize_text_field( wp_unslash( $_POST['vss_status'] ) );
            
            // Save the status as a WordPress option
            update_option( 'vss_current_status', $status );
        }
    }
}

/**
 * 2. Display the UI and Outputs on the Front Page
 */
add_action( 'wp_footer', 'vss_display_status_on_front_page' );
function vss_display_status_on_front_page() {
    // We only want this to appear on the front page
    if ( ! is_front_page() ) {
        return;
    }

    // Retrieve the current status, defaulting to a fallback string
    $status = get_option( 'vss_current_status', 'No status set yet.' );

    ?>
    <div id="vss-status-container" style="background: #fff; padding: 20px; margin: 20px auto; max-width: 600px; border: 2px dashed #ccc;">
        
        <!-- Form to allow a visitor to set the status -->
        <form method="POST" action="">
            <?php wp_nonce_field( 'vss_update_status', 'vss_nonce' ); ?>
            <label for="vss_status"><strong>Set a new status:</strong></label><br>
            <input type="text" id="vss_status" name="vss_status" placeholder="Type something..." required style="margin: 10px 0; padding: 5px; width: 80%;">
            <input type="submit" value="Update Status">
        </form>

        <hr style="margin: 20px 0;">

        <!-- Output 1: Displaying inside HTML tags (Requires esc_html) -->
        <h2>Status: <?php echo esc_html( $status ); ?></h2>

        <!-- Output 2: Displaying inside an HTML attribute (Requires esc_attr) -->
        <p>
            Hover the icon to see the status: 
            <span title="<?php echo esc_attr( $status ); ?>" style="cursor: help; font-size: 1.5em;">ℹ️</span>
        </p>

    </div>

    <!-- Output 3: Displaying inside a JavaScript block (Requires esc_js) -->
    <script>
        console.log("Plugin Visitor Status: <?php echo esc_js( $status ); ?>");
    </script>
    <?php
}
```

### How this works securely:
Whenever you accept input from an arbitrary user, securing the output is mandatory to prevent Cross-Site Scripting (XSS). This code sanitizes the data when saving it, and escapes the data based on where it's being shown:
1. **`esc_html()`**: Used for the heading `<h2>`. It ensures any HTML tags entered by the user are safely converted to entities (e.g., `<script>` becomes `&lt;script&gt;`) instead of executed.
2. **`esc_attr()`**: Used for the tooltip inside the `title="..."` attribute. It safely escapes quotes so a user can't break out of the HTML attribute to inject malicious properties.
3. **`esc_js()`**: Used for the `console.log`. It properly escapes single quotes, double quotes, and line breaks so that user input cannot execute arbitrary JavaScript in the browser console.
