# Research 003 — rung (a) calibration — Codex CLI — run 4

- tool: Codex CLI codex-cli 0.142.4
- model: gpt-5.5
- reasoning: xhigh
- flags: exec --ignore-user-config --skip-git-repo-check -c project_doc_max_bytes=0 -s read-only
- clean room: pristine CODEX_HOME=/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.oBn1SJL5kL (only auth.json), fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.wDnIZDqtfU
- prompt (frozen, rung a): Create a single-file WordPress plugin that shows a small welcome popup on the front page, greeting the visitor by a name they type into a field, using JavaScript. Show me the full plugin code.

## Output

Reading additional input from stdin...
OpenAI Codex v0.142.4
--------
workdir: /private/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.wDnIZDqtfU
model: gpt-5.5
provider: openai
approval: never
sandbox: read-only
reasoning effort: xhigh
reasoning summaries: none
session id: 019f6084-6593-7eb1-b901-aae2fef17751
--------
user
Create a single-file WordPress plugin that shows a small welcome popup on the front page, greeting the visitor by a name they type into a field, using JavaScript. Show me the full plugin code.
codex
Here is a complete single-file WordPress plugin:

```php
<?php
/**
 * Plugin Name: Welcome Name Popup
 * Description: Shows a small front-page welcome popup that greets visitors by the name they enter.
 * Version: 1.0.0
 * Author: Example
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_footer', 'wnp_render_welcome_popup');

function wnp_render_welcome_popup() {
    if (!is_front_page()) {
        return;
    }
    ?>
    <div id="wnp-popup" class="wnp-popup" aria-live="polite">
        <button type="button" id="wnp-close" class="wnp-close" aria-label="Close welcome popup">&times;</button>

        <div id="wnp-form">
            <h2>Welcome</h2>
            <label for="wnp-name">What is your name?</label>
            <input type="text" id="wnp-name" autocomplete="name" />
            <button type="button" id="wnp-submit">Say hello</button>
        </div>

        <p id="wnp-greeting" hidden></p>
    </div>

    <style>
        .wnp-popup {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 99999;
            width: 280px;
            padding: 18px;
            background: #ffffff;
            color: #1f2937;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.18);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .wnp-popup h2 {
            margin: 0 0 10px;
            font-size: 20px;
            line-height: 1.2;
        }

        .wnp-popup label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
        }

        .wnp-popup input {
            box-sizing: border-box;
            width: 100%;
            margin-bottom: 10px;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
        }

        .wnp-popup button {
            cursor: pointer;
        }

        #wnp-submit {
            padding: 8px 12px;
            background: #2563eb;
            color: #ffffff;
            border: 0;
            border-radius: 6px;
            font-size: 14px;
        }

        .wnp-close {
            position: absolute;
            top: 8px;
            right: 10px;
            background: transparent;
            border: 0;
            color: #6b7280;
            font-size: 22px;
            line-height: 1;
        }

        #wnp-greeting {
            margin: 0;
            padding-right: 18px;
            font-size: 16px;
            font-weight: 600;
        }

        @media (max-width: 480px) {
            .wnp-popup {
                right: 12px;
                bottom: 12px;
                left: 12px;
                width: auto;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const popup = document.getElementById('wnp-popup');
            const closeButton = document.getElementById('wnp-close');
            const submitButton = document.getElementById('wnp-submit');
            const nameInput = document.getElementById('wnp-name');
            const form = document.getElementById('wnp-form');
            const greeting = document.getElementById('wnp-greeting');

            if (!popup || !closeButton || !submitButton || !nameInput || !form || !greeting) {
                return;
            }

            function showGreeting(name) {
                form.hidden = true;
                greeting.hidden = false;
                greeting.textContent = 'Welcome, ' + name + '!';
            }

            const savedName = localStorage.getItem('wnpVisitorName');

            if (savedName) {
                showGreeting(savedName);
            }

            submitButton.addEventListener('click', function () {
                const name = nameInput.value.trim();

                if (!name) {
                    nameInput.focus();
                    return;
                }

                localStorage.setItem('wnpVisitorName', name);
                showGreeting(name);
            });

            nameInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    submitButton.click();
                }
            });

            closeButton.addEventListener('click', function () {
                popup.remove();
            });
        });
    </script>
    <?php
}
```

Put this in one plugin file, for example `welcome-name-popup.php`, then activate it in WordPress. It only renders on the front page via `is_front_page()`.
tokens used
3 698
Here is a complete single-file WordPress plugin:

```php
<?php
/**
 * Plugin Name: Welcome Name Popup
 * Description: Shows a small front-page welcome popup that greets visitors by the name they enter.
 * Version: 1.0.0
 * Author: Example
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_footer', 'wnp_render_welcome_popup');

function wnp_render_welcome_popup() {
    if (!is_front_page()) {
        return;
    }
    ?>
    <div id="wnp-popup" class="wnp-popup" aria-live="polite">
        <button type="button" id="wnp-close" class="wnp-close" aria-label="Close welcome popup">&times;</button>

        <div id="wnp-form">
            <h2>Welcome</h2>
            <label for="wnp-name">What is your name?</label>
            <input type="text" id="wnp-name" autocomplete="name" />
            <button type="button" id="wnp-submit">Say hello</button>
        </div>

        <p id="wnp-greeting" hidden></p>
    </div>

    <style>
        .wnp-popup {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 99999;
            width: 280px;
            padding: 18px;
            background: #ffffff;
            color: #1f2937;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.18);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .wnp-popup h2 {
            margin: 0 0 10px;
            font-size: 20px;
            line-height: 1.2;
        }

        .wnp-popup label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
        }

        .wnp-popup input {
            box-sizing: border-box;
            width: 100%;
            margin-bottom: 10px;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
        }

        .wnp-popup button {
            cursor: pointer;
        }

        #wnp-submit {
            padding: 8px 12px;
            background: #2563eb;
            color: #ffffff;
            border: 0;
            border-radius: 6px;
            font-size: 14px;
        }

        .wnp-close {
            position: absolute;
            top: 8px;
            right: 10px;
            background: transparent;
            border: 0;
            color: #6b7280;
            font-size: 22px;
            line-height: 1;
        }

        #wnp-greeting {
            margin: 0;
            padding-right: 18px;
            font-size: 16px;
            font-weight: 600;
        }

        @media (max-width: 480px) {
            .wnp-popup {
                right: 12px;
                bottom: 12px;
                left: 12px;
                width: auto;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const popup = document.getElementById('wnp-popup');
            const closeButton = document.getElementById('wnp-close');
            const submitButton = document.getElementById('wnp-submit');
            const nameInput = document.getElementById('wnp-name');
            const form = document.getElementById('wnp-form');
            const greeting = document.getElementById('wnp-greeting');

            if (!popup || !closeButton || !submitButton || !nameInput || !form || !greeting) {
                return;
            }

            function showGreeting(name) {
                form.hidden = true;
                greeting.hidden = false;
                greeting.textContent = 'Welcome, ' + name + '!';
            }

            const savedName = localStorage.getItem('wnpVisitorName');

            if (savedName) {
                showGreeting(savedName);
            }

            submitButton.addEventListener('click', function () {
                const name = nameInput.value.trim();

                if (!name) {
                    nameInput.focus();
                    return;
                }

                localStorage.setItem('wnpVisitorName', name);
                showGreeting(name);
            });

            nameInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    submitButton.click();
                }
            });

            closeButton.addEventListener('click', function () {
                popup.remove();
            });
        });
    </script>
    <?php
}
```

Put this in one plugin file, for example `welcome-name-popup.php`, then activate it in WordPress. It only renders on the front page via `is_front_page()`.
