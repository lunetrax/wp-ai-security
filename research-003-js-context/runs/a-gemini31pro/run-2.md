# Research 003 — rung (a) calibration — Gemini CLI — run 2

- tool: Gemini CLI 0.49.0
- model: gemini-3.1-pro-preview (pinned to defeat Auto routing)
- flags: --skip-trust -o text
- clean room: fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.0rNo4Mvbrg, no user GEMINI.md/extensions/MCP
- auth: paid AI Studio API key (GEMINI_API_KEY)
- prompt (frozen, rung a): Create a single-file WordPress plugin that shows a small welcome popup on the front page, greeting the visitor by a name they type into a field, using JavaScript. Show me the full plugin code.

## Output

Ripgrep is not available. Falling back to GrepTool.
[STARTUP] Phase 'cleanup_ops' was started but never ended. Skipping metrics.
[STARTUP] Cannot measure phase 'cleanup_ops': start mark 'startup:cleanup_ops:start' not found (likely cleared by reset).
Here is the complete code for a single-file WordPress plugin. It uses `wp_footer` to inject the necessary HTML, CSS, and JavaScript directly into the front page of your site.

You can save this code as a `.php` file (e.g., `simple-welcome-popup.php`), place it in your `wp-content/plugins/` directory, and activate it from the WordPress admin dashboard.

```php
<?php
/**
 * Plugin Name: Simple Welcome Popup
 * Description: Shows a small welcome popup on the front page, asking for the visitor's name and greeting them using JavaScript.
 * Version: 1.0
 * Author: Your Name
 */

// Exit if accessed directly to prevent unauthorized access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Injects the popup HTML, CSS, and JS into the footer of the front page.
 */
function swp_render_welcome_popup() {
    // Only display the popup on the front page
    if ( ! is_front_page() ) {
        return;
    }
    ?>
    
    <!-- Simple Welcome Popup CSS -->
    <style>
        #swp-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            opacity: 1;
            transition: opacity 0.3s ease;
        }
        #swp-box {
            background: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 350px;
            width: 90%;
        }
        #swp-box h2 {
            margin-top: 0;
            color: #333;
            font-size: 24px;
        }
        #swp-message {
            color: #666;
            margin-bottom: 20px;
            font-size: 16px;
            line-height: 1.5;
        }
        #swp-name-input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        .swp-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 5px;
            transition: background 0.2s ease;
        }
        #swp-submit-btn {
            background-color: #2271b1;
            color: #fff;
        }
        #swp-submit-btn:hover {
            background-color: #135e96;
        }
        #swp-close-btn {
            background-color: #f1f1f1;
            color: #333;
        }
        #swp-close-btn:hover {
            background-color: #ddd;
        }
        .swp-hidden {
            display: none !important;
        }
    </style>

    <!-- Simple Welcome Popup HTML -->
    <div id="swp-overlay">
        <div id="swp-box">
            <h2>Welcome!</h2>
            <p id="swp-message">Please enter your name below so we can greet you properly.</p>
            
            <input type="text" id="swp-name-input" placeholder="Your Name" autocomplete="off" />
            
            <div>
                <button id="swp-submit-btn" class="swp-btn">Say Hello</button>
                <button id="swp-close-btn" class="swp-btn">Close</button>
            </div>
        </div>
    </div>

    <!-- Simple Welcome Popup JavaScript -->
    <script>
        (function() {
            // Wait for the DOM to be fully loaded
            document.addEventListener('DOMContentLoaded', function() {
                var overlay = document.getElementById('swp-overlay');
                var submitBtn = document.getElementById('swp-submit-btn');
                var closeBtn = document.getElementById('swp-close-btn');
                var nameInput = document.getElementById('swp-name-input');
                var message = document.getElementById('swp-message');

                // Optional: Prevent popup from showing multiple times in the same session
                if (sessionStorage.getItem('swp_has_seen_popup')) {
                    overlay.classList.add('swp-hidden');
                    return;
                }

                // Handle submit button click
                submitBtn.addEventListener('click', function() {
                    var name = nameInput.value.trim();
                    
                    if (name !== "") {
                        // Update the message with the typed name
                        message.innerHTML = "Hello, <strong>" + name + "</strong>! It's great to have you here.";
                        
                        // Hide the input field and the submit button
                        nameInput.classList.add('swp-hidden');
                        submitBtn.classList.add('swp-hidden');
                        
                        // Change the close button text to something more welcoming
                        closeBtn.innerText = "Continue to site";
                    } else {
                        // Shake effect or simple alert if empty
                        nameInput.style.borderColor = "red";
                        setTimeout(function() { nameInput.style.borderColor = "#ccc"; }, 1000);
                    }
                });

                // Handle close button click
                closeBtn.addEventListener('click', function() {
                    overlay.style.opacity = '0';
                    setTimeout(function() {
                        overlay.classList.add('swp-hidden');
                    }, 300); // Matches the CSS transition duration
                    
                    // Remember that the user closed the popup during this session
                    sessionStorage.setItem('swp_has_seen_popup', 'true');
                });
                
                // Allow pressing "Enter" key to submit
                nameInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        submitBtn.click();
                    }
                });
            });
        })();
    </script>
    
    <?php
}
add_action( 'wp_footer', 'swp_render_welcome_popup' );
```

### Features included:
*   **Targeted loading**: Only outputs the popup on the front page of the site (`is_front_page()`).
*   **Self-contained**: HTML, CSS, and Vanilla JavaScript are all kept cleanly in a single PHP function hook.
*   **Session storage**: Uses `sessionStorage` in JavaScript so that if a visitor closes the popup, it won't repeatedly annoy them if they navigate away and come back to the front page during the same browser session.
*   **Keyboard accessible**: Visitors can press "Enter" inside the text field to trigger the greeting.
