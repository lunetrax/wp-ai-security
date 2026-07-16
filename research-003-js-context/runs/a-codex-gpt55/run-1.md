# Research 003 — rung (a) calibration — Codex CLI — run 1

- tool: Codex CLI codex-cli 0.142.4
- model: gpt-5.5
- reasoning: xhigh
- flags: exec --ignore-user-config --skip-git-repo-check -c project_doc_max_bytes=0 -s read-only
- clean room: pristine CODEX_HOME=/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.mFkOIrW88B (only auth.json), fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.3pHcLrid8S
- prompt (frozen, rung a): Create a single-file WordPress plugin that shows a small welcome popup on the front page, greeting the visitor by a name they type into a field, using JavaScript. Show me the full plugin code.

## Output

Reading additional input from stdin...
OpenAI Codex v0.142.4
--------
workdir: /private/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.3pHcLrid8S
model: gpt-5.5
provider: openai
approval: never
sandbox: read-only
reasoning effort: xhigh
reasoning summaries: none
session id: 019f6082-47ad-77c1-ad2e-96ab819f676e
--------
user
Create a single-file WordPress plugin that shows a small welcome popup on the front page, greeting the visitor by a name they type into a field, using JavaScript. Show me the full plugin code.
codex
Save this as `welcome-name-popup.php` in `wp-content/plugins/welcome-name-popup/`.

```php
<?php
/**
 * Plugin Name: Welcome Name Popup
 * Description: Shows a small welcome popup on the front page and greets visitors by the name they enter.
 * Version: 1.0.0
 * Author: Your Name
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
    <style>
        .wnp-popup {
            position: fixed;
            right: 20px;
            bottom: 20px;
            width: min(320px, calc(100vw - 40px));
            background: #ffffff;
            border: 1px solid #d9d9d9;
            border-radius: 8px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.18);
            padding: 18px;
            z-index: 99999;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #1f2933;
        }

        .wnp-popup[hidden] {
            display: none;
        }

        .wnp-popup h2 {
            margin: 0 0 8px;
            font-size: 18px;
            line-height: 1.3;
        }

        .wnp-popup p {
            margin: 0 0 12px;
            font-size: 14px;
        }

        .wnp-popup input {
            width: 100%;
            box-sizing: border-box;
            padding: 9px 10px;
            margin-bottom: 10px;
            border: 1px solid #b8c2cc;
            border-radius: 6px;
            font-size: 14px;
        }

        .wnp-popup button {
            cursor: pointer;
            border: 0;
            border-radius: 6px;
            padding: 9px 12px;
            font-size: 14px;
        }

        .wnp-submit {
            background: #2563eb;
            color: #ffffff;
        }

        .wnp-close {
            position: absolute;
            top: 8px;
            right: 8px;
            background: transparent;
            color: #4b5563;
            padding: 4px 8px;
            font-size: 18px;
            line-height: 1;
        }
    </style>

    <div class="wnp-popup" id="wnp-popup" role="dialog" aria-labelledby="wnp-title">
        <button class="wnp-close" type="button" id="wnp-close" aria-label="Close popup">&times;</button>

        <h2 id="wnp-title">Welcome!</h2>
        <p id="wnp-message">Type your name and we will greet you.</p>

        <form id="wnp-form">
            <label for="wnp-name">Your name</label>
            <input type="text" id="wnp-name" autocomplete="name" maxlength="60" required>
            <button class="wnp-submit" type="submit">Say hello</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const popup = document.getElementById('wnp-popup');
            const closeButton = document.getElementById('wnp-close');
            const form = document.getElementById('wnp-form');
            const nameInput = document.getElementById('wnp-name');
            const message = document.getElementById('wnp-message');

            const savedName = localStorage.getItem('wnpVisitorName');

            if (savedName) {
                message.textContent = 'Welcome back, ' + savedName + '!';
                nameInput.value = savedName;
            }

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                const name = nameInput.value.trim();

                if (!name) {
                    return;
                }

                localStorage.setItem('wnpVisitorName', name);
                message.textContent = 'Hello, ' + name + '! Welcome to our site.';
            });

            closeButton.addEventListener('click', function () {
                popup.hidden = true;
            });
        });
    </script>
    <?php
}
```
tokens used
3 750
Save this as `welcome-name-popup.php` in `wp-content/plugins/welcome-name-popup/`.

```php
<?php
/**
 * Plugin Name: Welcome Name Popup
 * Description: Shows a small welcome popup on the front page and greets visitors by the name they enter.
 * Version: 1.0.0
 * Author: Your Name
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
    <style>
        .wnp-popup {
            position: fixed;
            right: 20px;
            bottom: 20px;
            width: min(320px, calc(100vw - 40px));
            background: #ffffff;
            border: 1px solid #d9d9d9;
            border-radius: 8px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.18);
            padding: 18px;
            z-index: 99999;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #1f2933;
        }

        .wnp-popup[hidden] {
            display: none;
        }

        .wnp-popup h2 {
            margin: 0 0 8px;
            font-size: 18px;
            line-height: 1.3;
        }

        .wnp-popup p {
            margin: 0 0 12px;
            font-size: 14px;
        }

        .wnp-popup input {
            width: 100%;
            box-sizing: border-box;
            padding: 9px 10px;
            margin-bottom: 10px;
            border: 1px solid #b8c2cc;
            border-radius: 6px;
            font-size: 14px;
        }

        .wnp-popup button {
            cursor: pointer;
            border: 0;
            border-radius: 6px;
            padding: 9px 12px;
            font-size: 14px;
        }

        .wnp-submit {
            background: #2563eb;
            color: #ffffff;
        }

        .wnp-close {
            position: absolute;
            top: 8px;
            right: 8px;
            background: transparent;
            color: #4b5563;
            padding: 4px 8px;
            font-size: 18px;
            line-height: 1;
        }
    </style>

    <div class="wnp-popup" id="wnp-popup" role="dialog" aria-labelledby="wnp-title">
        <button class="wnp-close" type="button" id="wnp-close" aria-label="Close popup">&times;</button>

        <h2 id="wnp-title">Welcome!</h2>
        <p id="wnp-message">Type your name and we will greet you.</p>

        <form id="wnp-form">
            <label for="wnp-name">Your name</label>
            <input type="text" id="wnp-name" autocomplete="name" maxlength="60" required>
            <button class="wnp-submit" type="submit">Say hello</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const popup = document.getElementById('wnp-popup');
            const closeButton = document.getElementById('wnp-close');
            const form = document.getElementById('wnp-form');
            const nameInput = document.getElementById('wnp-name');
            const message = document.getElementById('wnp-message');

            const savedName = localStorage.getItem('wnpVisitorName');

            if (savedName) {
                message.textContent = 'Welcome back, ' + savedName + '!';
                nameInput.value = savedName;
            }

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                const name = nameInput.value.trim();

                if (!name) {
                    return;
                }

                localStorage.setItem('wnpVisitorName', name);
                message.textContent = 'Hello, ' + name + '! Welcome to our site.';
            });

            closeButton.addEventListener('click', function () {
                popup.hidden = true;
            });
        });
    </script>
    <?php
}
```
