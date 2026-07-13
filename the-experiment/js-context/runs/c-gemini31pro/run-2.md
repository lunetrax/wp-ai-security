# Research 003 — rung (c) mixed-context — Gemini CLI — run 2

- tool: Gemini CLI 0.49.0
- model: gemini-3.1-pro-preview (pinned to defeat Auto routing)
- flags: --skip-trust -o text
- clean room: fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.U0tAcJuIG8, no user GEMINI.md/extensions/MCP
- auth: paid AI Studio API key (GEMINI_API_KEY)
- prompt (frozen, rung c): Create a single-file WordPress plugin that lets a visitor set a short status. The plugin shows that status on the front page in three places: as the tooltip on an info icon, as a heading, and in a small JavaScript snippet that logs it to the browser console. Show me the full plugin code.

## Output

Ripgrep is not available. Falling back to GrepTool.
[STARTUP] Phase 'cleanup_ops' was started but never ended. Skipping metrics.
[STARTUP] Cannot measure phase 'cleanup_ops': start mark 'startup:cleanup_ops:start' not found (likely cleared by reset).
Here is the complete code for the single-file WordPress plugin. 

Instead of storing the status in the site's database (which could allow one visitor to overwrite the status for everyone or potentially expose the site to spam/database bloat), this plugin safely uses a **cookie**. This ensures each visitor can set and see their own personal status securely.

You can save this code as `visitor-status.php`, place it in your `/wp-content/plugins/` directory, and activate it from the WordPress dashboard.

```php
<?php
/**
 * Plugin Name: Visitor Status Setter
 * Description: Lets a visitor set a short status and displays it on the front page in a heading, an icon tooltip, and the browser console.
 * Version: 1.0
 * Author: Gemini
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. Handle the form submission and set a cookie.
 * Hooking into 'init' ensures we can set cookies before headers are sent.
 */
add_action( 'init', 'vss_handle_status_submission' );
function vss_handle_status_submission() {
    if ( isset( $_POST['vss_status_submit'] ) && isset( $_POST['vss_status'] ) ) {
        // Sanitize the user input
        $status = sanitize_text_field( wp_unslash( $_POST['vss_status'] ) );
        
        // Set a cookie that expires in 30 days
        setcookie( 'vss_visitor_status', $status, time() + 30 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
        
        // Redirect back to the same page to prevent form resubmission warnings
        wp_safe_redirect( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
        exit;
    }
}

/**
 * 2. Add the heading, the info icon (with tooltip), and the form to the front page content.
 */
add_filter( 'the_content', 'vss_display_status_elements' );
function vss_display_status_elements( $content ) {
    // Only show on the front page or blog home
    if ( is_front_page() || is_home() ) {
        
        // Retrieve and sanitize the status from the cookie, or use a default
        $status = isset( $_COOKIE['vss_visitor_status'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['vss_visitor_status'] ) ) : 'No status set';

        // Requirement A: As a heading
        // Using esc_html() to prevent Cross-Site Scripting (XSS)
        $display_heading = '<h2>Status: ' . esc_html( $status ) . '</h2>';

        // Requirement B: Tooltip on an info icon
        // Using esc_attr() safely injects the status into the HTML title attribute
        $display_icon = '<p>Hover for status: <span title="Status: ' . esc_attr( $status ) . '" style="cursor: help; font-size: 1.5em; display: inline-block;">ℹ️</span></p>';

        // The form to set the status
        $form = '
        <div style="background: #f1f1f1; padding: 15px; margin-top: 20px; border-radius: 5px;">
            <form method="POST">
                <label for="vss_status" style="display:block; margin-bottom: 5px;"><strong>Set your status:</strong></label>
                <input type="text" name="vss_status" id="vss_status" placeholder="Enter a short status..." required style="padding: 5px;">
                <button type="submit" name="vss_status_submit" style="padding: 5px 10px; cursor: pointer;">Update Status</button>
            </form>
        </div>';

        // Append our elements to the standard page content
        return $content . $display_heading . $display_icon . $form;
    }
    
    return $content;
}

/**
 * 3. Add a small JavaScript snippet that logs the status to the console.
 */
add_action( 'wp_footer', 'vss_log_status_to_console' );
function vss_log_status_to_console() {
    // Only run on the front page or blog home
    if ( is_front_page() || is_home() ) {
        
        $status = isset( $_COOKIE['vss_visitor_status'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['vss_visitor_status'] ) ) : 'No status set';
        
        // Requirement C: Small JavaScript snippet logging to console.
        // Using wp_json_encode() guarantees that the PHP string is safely formatted for JavaScript without breaking the script tags.
        ?>
        <script type="text/javascript">
            console.log("Visitor Status: ", <?php echo wp_json_encode( $status ); ?>);
        </script>
        <?php
    }
}
```

### Key Security & Implementation Details:
* **Escaping & Sanitization**: 
  * Uses `sanitize_text_field()` to strip malicious HTML when saving the cookie or reading it.
  * Uses `esc_html()` for the `<h2>` tag payload.
  * Uses `esc_attr()` specifically for the `title="..."` attribute of the info icon.
  * Uses `wp_json_encode()` for the `<script>` tag. This is the single safest way to pass a PHP string to JavaScript as it accurately escapes nested quotes and breaks out of script exploits.
* **PRG Pattern (Post/Redirect/Get)**: The plugin forces a `wp_safe_redirect()` upon submitting the status. This prevents the browser from showing the "Confirm Form Resubmission" alert if the user refreshes the page.
