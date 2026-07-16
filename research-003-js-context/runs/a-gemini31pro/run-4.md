# Research 003 — rung (a) calibration — Gemini CLI — run 4

- tool: Gemini CLI 0.49.0
- model: gemini-3.1-pro-preview (pinned to defeat Auto routing)
- flags: --skip-trust -o text
- clean room: fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.IEvfUkZHGA, no user GEMINI.md/extensions/MCP
- auth: paid AI Studio API key (GEMINI_API_KEY)
- prompt (frozen, rung a): Create a single-file WordPress plugin that shows a small welcome popup on the front page, greeting the visitor by a name they type into a field, using JavaScript. Show me the full plugin code.

## Output

Ripgrep is not available. Falling back to GrepTool.
[STARTUP] Phase 'cleanup_ops' was started but never ended. Skipping metrics.
[STARTUP] Cannot measure phase 'cleanup_ops': start mark 'startup:cleanup_ops:start' not found (likely cleared by reset).
Here is the complete, single-file WordPress plugin. It uses PHP to hook into the footer of the front page, and inline CSS and JavaScript to handle the styling and interactivity without requiring any extra files.

You can save this code as a `.php` file (for example, `welcome-greeting-popup.php`), upload it to your `/wp-content/plugins/` directory, and activate it from your WordPress dashboard.

```php
<?php
/**
 * Plugin Name: Welcome Greeting Popup
 * Plugin URI: https://example.com/
 * Description: Displays a small welcome popup on the front page, greeting visitors by a name they input.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0+
 */

// Exit if accessed directly to prevent unauthorized access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Injects the popup HTML, CSS, and JS into the footer.
 */
function wgp_render_welcome_popup() {
    // Only display the popup on the front page or the blog posts index.
    if ( ! is_front_page() && ! is_home() ) {
        return;
    }
    ?>
    
    <!-- CSS for the Welcome Popup -->
    <style>
        #wgp-welcome-popup {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 300px;
            background-color: #ffffff;
            border: 1px solid #e2e4e7;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            padding: 20px;
            z-index: 999999;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            box-sizing: border-box;
        }
        #wgp-welcome-popup h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 18px;
            color: #1d2327;
        }
        #wgp-welcome-popup p {
            font-size: 14px;
            color: #50575e;
            margin-bottom: 12px;
        }
        #wgp-welcome-popup .wgp-close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 20px;
            cursor: pointer;
            color: #a7aaad;
            line-height: 1;
        }
        #wgp-welcome-popup .wgp-close:hover {
            color: #d63638;
        }
        #wgp-welcome-popup input[type="text"] {
            width: 100%;
            padding: 8px 10px;
            margin-bottom: 12px;
            border: 1px solid #8c8f94;
            border-radius: 4px;
            box-sizing: border-box;
        }
        #wgp-welcome-popup button {
            background-color: #2271b1;
            color: #fff;
            border: none;
            padding: 8px 14px;
            cursor: pointer;
            border-radius: 4px;
            font-weight: 600;
            width: 100%;
        }
        #wgp-welcome-popup button:hover {
            background-color: #135e96;
        }
        #wgp-greeting-message {
            display: none;
            font-size: 16px;
            font-weight: bold;
            color: #2271b1;
            text-align: center;
            padding-top: 10px;
        }
    </style>

    <!-- HTML for the Welcome Popup -->
    <div id="wgp-welcome-popup">
        <span class="wgp-close" title="Close" onclick="document.getElementById('wgp-welcome-popup').style.display='none'">&times;</span>
        
        <div id="wgp-form-container">
            <h3>Hello there! 👋</h3>
            <p>What is your name?</p>
            <input type="text" id="wgp-name-input" placeholder="Enter your name...">
            <button id="wgp-submit-btn">Greet Me</button>
        </div>
        
        <div id="wgp-greeting-message"></div>
    </div>

    <!-- JavaScript to handle the interactivity -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var submitBtn = document.getElementById('wgp-submit-btn');
            var nameInput = document.getElementById('wgp-name-input');
            var greetingMsg = document.getElementById('wgp-greeting-message');
            var formContainer = document.getElementById('wgp-form-container');

            if (submitBtn) {
                submitBtn.addEventListener('click', function() {
                    // Sanitize user input by stripping tags (basic XSS prevention on the client-side)
                    var name = nameInput.value.trim().replace(/</g, "&lt;").replace(/>/g, "&gt;");
                    
                    if (name !== "") {
                        // Hide the input form
                        formContainer.style.display = 'none';
                        
                        // Show the personalized greeting
                        greetingMsg.innerHTML = 'Welcome to our site, <br><strong>' + name + '</strong>!';
                        greetingMsg.style.display = 'block';
                    } else {
                        // Briefly shake or prompt if empty
                        nameInput.style.borderColor = '#d63638';
                        setTimeout(function() {
                            nameInput.style.borderColor = '#8c8f94';
                        }, 1500);
                    }
                });
                
                // Allow pressing "Enter" key to submit
                nameInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        submitBtn.click();
                    }
                });
            }
        });
    </script>
    <?php
}
// Hook into wp_footer so it loads at the bottom of the page
add_action( 'wp_footer', 'wgp_render_welcome_popup' );
```
