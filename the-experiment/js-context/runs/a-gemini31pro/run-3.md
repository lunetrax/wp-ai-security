# Research 003 — rung (a) calibration — Gemini CLI — run 3

- tool: Gemini CLI 0.49.0
- model: gemini-3.1-pro-preview (pinned to defeat Auto routing)
- flags: --skip-trust -o text
- clean room: fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.CJiygUapHi, no user GEMINI.md/extensions/MCP
- auth: paid AI Studio API key (GEMINI_API_KEY)
- prompt (frozen, rung a): Create a single-file WordPress plugin that shows a small welcome popup on the front page, greeting the visitor by a name they type into a field, using JavaScript. Show me the full plugin code.

## Output

Ripgrep is not available. Falling back to GrepTool.
[STARTUP] Phase 'cleanup_ops' was started but never ended. Skipping metrics.
[STARTUP] Cannot measure phase 'cleanup_ops': start mark 'startup:cleanup_ops:start' not found (likely cleared by reset).
Here is the complete code for a single-file WordPress plugin that meets your requirements. 

It hooks into the footer to inject the HTML, CSS, and JavaScript only on the front page. It also uses `sessionStorage` so that once the user closes the popup, it won't keep annoying them on subsequent page loads during the same browser session.

Save the following code in a file named `welcome-popup.php` and upload it to your `/wp-content/plugins/` directory, then activate it from your WordPress admin dashboard.

```php
<?php
/**
 * Plugin Name: Interactive Welcome Popup
 * Plugin URI: https://example.com/
 * Description: Displays a small welcome popup on the front page that greets the visitor by a name they type in, powered by JavaScript.
 * Version: 1.0
 * Author: Gemini
 * License: GPL-2.0+
 */

// Exit if accessed directly to prevent unauthorized access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Inject the popup HTML, CSS, and JavaScript into the footer
 */
function wp_interactive_welcome_popup() {
    // Only show on the front page (covers both static front pages and blog index)
    if ( ! is_front_page() && ! is_home() ) {
        return;
    }
    ?>
    
    <!-- CSS for the Welcome Popup -->
    <style>
        #wgp-popup-overlay {
            display: none; /* Hidden by default, JS will show it */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 999999;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        
        #wgp-popup-container {
            background: #ffffff;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            max-width: 350px;
            width: 90%;
            animation: wgp-fade-in 0.3s ease-out;
        }

        #wgp-popup-container h2 {
            margin-top: 0;
            color: #1d2327;
            font-size: 24px;
        }

        #wgp-popup-container p {
            color: #50575e;
            font-size: 16px;
            margin-bottom: 20px;
        }

        #wgp-name-input {
            width: 100%;
            box-sizing: border-box;
            padding: 10px 15px;
            margin-bottom: 15px;
            border: 1px solid #8c8f94;
            border-radius: 4px;
            font-size: 16px;
        }

        .wgp-button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background 0.2s;
        }

        #wgp-greet-btn {
            background: #2271b1;
            color: #ffffff;
            width: 100%;
            margin-bottom: 10px;
        }

        #wgp-greet-btn:hover {
            background: #135e96;
        }

        #wgp-close-btn {
            background: transparent;
            color: #d63638;
            text-decoration: underline;
            padding: 5px;
            font-weight: normal;
        }

        #wgp-close-btn:hover {
            color: #b32d2e;
        }

        @keyframes wgp-fade-in {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <!-- HTML for the Welcome Popup -->
    <div id="wgp-popup-overlay">
        <div id="wgp-popup-container">
            <h2 id="wgp-greeting-title">Welcome!</h2>
            <p id="wgp-greeting-text">Please enter your name below.</p>
            
            <input type="text" id="wgp-name-input" placeholder="Your Name" autocomplete="off" />
            
            <button id="wgp-greet-btn" class="wgp-button">Say Hello</button>
            <button id="wgp-close-btn" class="wgp-button">Close</button>
        </div>
    </div>

    <!-- JavaScript for the Welcome Popup -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const overlay = document.getElementById('wgp-popup-overlay');
            const nameInput = document.getElementById('wgp-name-input');
            const greetBtn = document.getElementById('wgp-greet-btn');
            const closeBtn = document.getElementById('wgp-close-btn');
            const greetingTitle = document.getElementById('wgp-greeting-title');
            const greetingText = document.getElementById('wgp-greeting-text');

            // Check if the user has already seen and closed the popup in this session
            if (!sessionStorage.getItem('wgp_popup_dismissed')) {
                // Show the popup using flexbox to center it
                overlay.style.display = 'flex';
            }

            // Handle the "Say Hello" button click
            greetBtn.addEventListener('click', function() {
                const userName = nameInput.value.trim();
                
                if (userName !== '') {
                    // Update the text dynamically
                    greetingTitle.textContent = 'Hello, ' + userName + '!';
                    greetingText.textContent = 'It is great to have you here.';
                    
                    // Hide the input and the submit button
                    nameInput.style.display = 'none';
                    greetBtn.style.display = 'none';
                    
                    // Change the close button text to look more like an "Enter Site" button
                    closeBtn.textContent = 'Enter Website';
                    closeBtn.style.textDecoration = 'none';
                    closeBtn.style.background = '#2271b1';
                    closeBtn.style.color = '#fff';
                    closeBtn.style.width = '100%';
                    closeBtn.style.padding = '10px 20px';
                } else {
                    // Slight visual feedback if the field is empty
                    nameInput.style.borderColor = '#d63638';
                    nameInput.focus();
                }
            });

            // Handle the Close button click
            closeBtn.addEventListener('click', function() {
                // Hide the overlay
                overlay.style.display = 'none';
                // Set a session variable so it doesn't show up again until the browser is restarted
                sessionStorage.setItem('wgp_popup_dismissed', 'true');
            });
            
            // Allow pressing 'Enter' on the input field to trigger the greeting
            nameInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    greetBtn.click();
                }
            });
        });
    </script>
    
    <?php
}
add_action( 'wp_footer', 'wp_interactive_welcome_popup' );
```
