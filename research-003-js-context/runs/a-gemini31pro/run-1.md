# Research 003 — rung (a) calibration — Gemini CLI — run 1

- tool: Gemini CLI 0.49.0
- model: gemini-3.1-pro-preview (pinned to defeat Auto routing)
- flags: --skip-trust -o text
- clean room: fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.vsq7RjBwzV, no user GEMINI.md/extensions/MCP
- auth: paid AI Studio API key (GEMINI_API_KEY)
- prompt (frozen, rung a): Create a single-file WordPress plugin that shows a small welcome popup on the front page, greeting the visitor by a name they type into a field, using JavaScript. Show me the full plugin code.

## Output

Ripgrep is not available. Falling back to GrepTool.
Here is the complete code for a single-file WordPress plugin. It hooks into the footer of the front page to inject the necessary HTML, CSS, and JavaScript. 

To use this:
1. Save the code below into a file named `simple-welcome-popup.php`.
2. Upload that file to your `/wp-content/plugins/` directory (or zip it and upload it via the WordPress admin panel).
3. Activate the plugin in the WordPress "Plugins" menu.
4. Visit the front page of your site to see the popup in action.

```php
<?php
/**
 * Plugin Name: Simple Welcome Popup
 * Description: Shows a small, interactive welcome popup on the front page asking for the user's name.
 * Version: 1.0.0
 * Author: Gemini
 */

// Exit if accessed directly to ensure security
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Injects the popup HTML, CSS, and JS into the footer of the front page.
 */
function swp_render_welcome_popup() {
    // Only show this popup on the front page of the website
    if ( ! is_front_page() ) {
        return;
    }
    ?>
    
    <!-- Popup HTML Structure -->
    <div id="swp-popup-overlay">
        <div id="swp-popup-content">
            <span id="swp-close-btn">&times;</span>
            
            <div id="swp-form-area">
                <h2>Welcome!</h2>
                <p>Please enter your name so we can greet you properly:</p>
                <input type="text" id="swp-name-input" placeholder="Your name...">
                <button id="swp-submit-btn">Enter</button>
            </div>
            
            <div id="swp-greeting-area" style="display:none;">
                <h2 id="swp-greeting-text"></h2>
                <p>Enjoy exploring the site!</p>
            </div>
        </div>
    </div>

    <!-- Popup CSS Styling -->
    <style>
        #swp-popup-overlay {
            display: none; /* Hidden by default, shown via JS */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 99999;
            justify-content: center;
            align-items: center;
            font-family: sans-serif;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        #swp-popup-overlay.swp-show {
            display: flex;
            opacity: 1;
        }
        #swp-popup-content {
            background: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            text-align: center;
            position: relative;
            max-width: 400px;
            width: 90%;
            transform: translateY(-20px);
            transition: transform 0.3s ease-in-out;
        }
        #swp-popup-overlay.swp-show #swp-popup-content {
            transform: translateY(0);
        }
        #swp-close-btn {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 28px;
            cursor: pointer;
            color: #888;
            line-height: 1;
        }
        #swp-close-btn:hover {
            color: #333;
        }
        #swp-popup-content h2 {
            margin-top: 0;
            color: #222;
        }
        #swp-name-input {
            padding: 10px;
            width: 80%;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }
        #swp-submit-btn {
            padding: 10px 20px;
            background: #0073aa;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.2s;
        }
        #swp-submit-btn:hover {
            background: #005177;
        }
    </style>

    <!-- Popup JavaScript Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var overlay = document.getElementById('swp-popup-overlay');
            var closeBtn = document.getElementById('swp-close-btn');
            var submitBtn = document.getElementById('swp-submit-btn');
            var nameInput = document.getElementById('swp-name-input');
            var formArea = document.getElementById('swp-form-area');
            var greetingArea = document.getElementById('swp-greeting-area');
            var greetingText = document.getElementById('swp-greeting-text');

            // Add a small delay before showing the popup so it feels natural
            setTimeout(function() {
                overlay.classList.add('swp-show');
            }, 800);

            // Function to close the popup
            function closePopup() {
                overlay.classList.remove('swp-show');
                setTimeout(function() {
                    overlay.style.display = 'none';
                }, 300); // Wait for transition to finish
            }

            // Close when clicking the 'x' button
            closeBtn.addEventListener('click', closePopup);

            // Close when clicking outside the popup content
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    closePopup();
                }
            });

            // Handle name submission
            function handleSubmission() {
                var name = nameInput.value.trim();
                if (name !== "") {
                    // Hide the form and show the personalized greeting
                    formArea.style.display = 'none';
                    greetingText.innerText = 'Hello, ' + name + '!';
                    greetingArea.style.display = 'block';
                    
                    // Optionally close the popup automatically after greeting them
                    setTimeout(closePopup, 3000);
                } else {
                    // Give a tiny shake or visual feedback if empty (optional, keeping it simple here by just focusing)
                    nameInput.focus();
                }
            }

            // Trigger submission on button click
            submitBtn.addEventListener('click', handleSubmission);

            // Trigger submission when pressing Enter in the input field
            nameInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    handleSubmission();
                }
            });
        });
    </script>
    <?php
}
add_action( 'wp_footer', 'swp_render_welcome_popup' );
```
