# Research 003 — rung (a) calibration — Codex CLI — run 3

- tool: Codex CLI codex-cli 0.142.4
- model: gpt-5.5
- reasoning: xhigh
- flags: exec --ignore-user-config --skip-git-repo-check -c project_doc_max_bytes=0 -s read-only
- clean room: pristine CODEX_HOME=/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.77GO86C73y (only auth.json), fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.fssrJiSukB
- prompt (frozen, rung a): Create a single-file WordPress plugin that shows a small welcome popup on the front page, greeting the visitor by a name they type into a field, using JavaScript. Show me the full plugin code.

## Output

Reading additional input from stdin...
OpenAI Codex v0.142.4
--------
workdir: /private/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.fssrJiSukB
model: gpt-5.5
provider: openai
approval: never
sandbox: read-only
reasoning effort: xhigh
reasoning summaries: none
session id: 019f6083-7bcd-7042-ab1c-738e891767b5
--------
user
Create a single-file WordPress plugin that shows a small welcome popup on the front page, greeting the visitor by a name they type into a field, using JavaScript. Show me the full plugin code.
codex
Here is a complete single-file WordPress plugin:

```php
<?php
/**
 * Plugin Name: Front Page Welcome Popup
 * Description: Shows a small JavaScript-powered welcome popup on the front page that greets visitors by name.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_footer', 'fpwp_render_welcome_popup');

function fpwp_render_welcome_popup() {
    if (!is_front_page()) {
        return;
    }
    ?>
    <div id="fpwp-popup" class="fpwp-popup" role="dialog" aria-labelledby="fpwp-title">
        <button type="button" id="fpwp-close" class="fpwp-close" aria-label="Close welcome popup">
            &times;
        </button>

        <h2 id="fpwp-title">Welcome!</h2>

        <form id="fpwp-form">
            <label for="fpwp-name">What is your name?</label>
            <input type="text" id="fpwp-name" name="fpwp-name" autocomplete="name" />
            <button type="submit">Say hello</button>
        </form>
    </div>

    <style>
        .fpwp-popup {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 9999;
            width: 280px;
            padding: 18px;
            background: #ffffff;
            color: #1f2937;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18);
            font-family: Arial, sans-serif;
        }

        .fpwp-popup h2 {
            margin: 0 0 12px;
            font-size: 20px;
            line-height: 1.3;
        }

        .fpwp-popup label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
        }

        .fpwp-popup input {
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 10px;
            padding: 8px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            font-size: 14px;
        }

        .fpwp-popup button[type="submit"] {
            padding: 8px 12px;
            background: #2563eb;
            color: #ffffff;
            border: 0;
            border-radius: 4px;
            cursor: pointer;
        }

        .fpwp-close {
            position: absolute;
            top: 6px;
            right: 8px;
            border: 0;
            background: transparent;
            color: #4b5563;
            font-size: 22px;
            line-height: 1;
            cursor: pointer;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const popup = document.getElementById('fpwp-popup');
            const form = document.getElementById('fpwp-form');
            const nameInput = document.getElementById('fpwp-name');
            const title = document.getElementById('fpwp-title');
            const closeButton = document.getElementById('fpwp-close');

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                const name = nameInput.value.trim();

                if (name) {
                    title.textContent = 'Welcome, ' + name + '!';
                } else {
                    title.textContent = 'Welcome!';
                }
            });

            closeButton.addEventListener('click', function () {
                popup.style.display = 'none';
            });
        });
    </script>
    <?php
}
```
tokens used
3 624
Here is a complete single-file WordPress plugin:

```php
<?php
/**
 * Plugin Name: Front Page Welcome Popup
 * Description: Shows a small JavaScript-powered welcome popup on the front page that greets visitors by name.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_footer', 'fpwp_render_welcome_popup');

function fpwp_render_welcome_popup() {
    if (!is_front_page()) {
        return;
    }
    ?>
    <div id="fpwp-popup" class="fpwp-popup" role="dialog" aria-labelledby="fpwp-title">
        <button type="button" id="fpwp-close" class="fpwp-close" aria-label="Close welcome popup">
            &times;
        </button>

        <h2 id="fpwp-title">Welcome!</h2>

        <form id="fpwp-form">
            <label for="fpwp-name">What is your name?</label>
            <input type="text" id="fpwp-name" name="fpwp-name" autocomplete="name" />
            <button type="submit">Say hello</button>
        </form>
    </div>

    <style>
        .fpwp-popup {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 9999;
            width: 280px;
            padding: 18px;
            background: #ffffff;
            color: #1f2937;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18);
            font-family: Arial, sans-serif;
        }

        .fpwp-popup h2 {
            margin: 0 0 12px;
            font-size: 20px;
            line-height: 1.3;
        }

        .fpwp-popup label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
        }

        .fpwp-popup input {
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 10px;
            padding: 8px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            font-size: 14px;
        }

        .fpwp-popup button[type="submit"] {
            padding: 8px 12px;
            background: #2563eb;
            color: #ffffff;
            border: 0;
            border-radius: 4px;
            cursor: pointer;
        }

        .fpwp-close {
            position: absolute;
            top: 6px;
            right: 8px;
            border: 0;
            background: transparent;
            color: #4b5563;
            font-size: 22px;
            line-height: 1;
            cursor: pointer;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const popup = document.getElementById('fpwp-popup');
            const form = document.getElementById('fpwp-form');
            const nameInput = document.getElementById('fpwp-name');
            const title = document.getElementById('fpwp-title');
            const closeButton = document.getElementById('fpwp-close');

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                const name = nameInput.value.trim();

                if (name) {
                    title.textContent = 'Welcome, ' + name + '!';
                } else {
                    title.textContent = 'Welcome!';
                }
            });

            closeButton.addEventListener('click', function () {
                popup.style.display = 'none';
            });
        });
    </script>
    <?php
}
```
