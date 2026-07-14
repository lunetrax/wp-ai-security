# Research 003 — rung (a) calibration — Codex CLI — run 2

- tool: Codex CLI codex-cli 0.142.4
- model: gpt-5.5
- reasoning: xhigh
- flags: exec --ignore-user-config --skip-git-repo-check -c project_doc_max_bytes=0 -s read-only
- clean room: pristine CODEX_HOME=/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.BvNWB2Gl88 (only auth.json), fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.Y6vg5n90nn
- prompt (frozen, rung a): Create a single-file WordPress plugin that shows a small welcome popup on the front page, greeting the visitor by a name they type into a field, using JavaScript. Show me the full plugin code.

## Output

Reading additional input from stdin...
OpenAI Codex v0.142.4
--------
workdir: /private/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.Y6vg5n90nn
model: gpt-5.5
provider: openai
approval: never
sandbox: read-only
reasoning effort: xhigh
reasoning summaries: none
session id: 019f6082-d64a-7983-8e36-aa799ae3cd47
--------
user
Create a single-file WordPress plugin that shows a small welcome popup on the front page, greeting the visitor by a name they type into a field, using JavaScript. Show me the full plugin code.
codex
Save this as `welcome-name-popup.php` in `wp-content/plugins/`, then activate it in WordPress.

```php
<?php
/**
 * Plugin Name: Front Page Welcome Popup
 * Description: Shows a small JavaScript-powered welcome popup on the front page and greets visitors by name.
 * Version: 1.0.0
 * Author: Your Name
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
    <div id="fpwp-popup" class="fpwp-popup" role="dialog" aria-modal="true" aria-labelledby="fpwp-title">
        <div class="fpwp-popup__box">
            <button type="button" class="fpwp-popup__close" aria-label="Close welcome popup">&times;</button>

            <h2 id="fpwp-title">Welcome</h2>

            <form id="fpwp-form">
                <label for="fpwp-name">What is your name?</label>
                <input type="text" id="fpwp-name" name="fpwp-name" autocomplete="name" required>
                <button type="submit">Say hello</button>
            </form>

            <p id="fpwp-message" class="fpwp-message" hidden></p>
        </div>
    </div>

    <style>
        .fpwp-popup {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 99999;
            max-width: 320px;
            font-family: inherit;
        }

        .fpwp-popup__box {
            position: relative;
            padding: 20px;
            background: #fff;
            color: #111;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.18);
        }

        .fpwp-popup__close {
            position: absolute;
            top: 8px;
            right: 10px;
            border: 0;
            background: transparent;
            font-size: 24px;
            line-height: 1;
            cursor: pointer;
        }

        .fpwp-popup label {
            display: block;
            margin-bottom: 6px;
        }

        .fpwp-popup input {
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 10px;
            padding: 8px;
        }

        .fpwp-popup button[type="submit"] {
            padding: 8px 12px;
            cursor: pointer;
        }

        .fpwp-message {
            margin: 12px 0 0;
            font-weight: 600;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const popup = document.getElementById('fpwp-popup');
            const form = document.getElementById('fpwp-form');
            const nameInput = document.getElementById('fpwp-name');
            const message = document.getElementById('fpwp-message');
            const closeButton = popup.querySelector('.fpwp-popup__close');

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                const name = nameInput.value.trim();

                if (!name) {
                    return;
                }

                message.textContent = 'Welcome, ' + name + '!';
                message.hidden = false;
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
3 575
Save this as `welcome-name-popup.php` in `wp-content/plugins/`, then activate it in WordPress.

```php
<?php
/**
 * Plugin Name: Front Page Welcome Popup
 * Description: Shows a small JavaScript-powered welcome popup on the front page and greets visitors by name.
 * Version: 1.0.0
 * Author: Your Name
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
    <div id="fpwp-popup" class="fpwp-popup" role="dialog" aria-modal="true" aria-labelledby="fpwp-title">
        <div class="fpwp-popup__box">
            <button type="button" class="fpwp-popup__close" aria-label="Close welcome popup">&times;</button>

            <h2 id="fpwp-title">Welcome</h2>

            <form id="fpwp-form">
                <label for="fpwp-name">What is your name?</label>
                <input type="text" id="fpwp-name" name="fpwp-name" autocomplete="name" required>
                <button type="submit">Say hello</button>
            </form>

            <p id="fpwp-message" class="fpwp-message" hidden></p>
        </div>
    </div>

    <style>
        .fpwp-popup {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 99999;
            max-width: 320px;
            font-family: inherit;
        }

        .fpwp-popup__box {
            position: relative;
            padding: 20px;
            background: #fff;
            color: #111;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.18);
        }

        .fpwp-popup__close {
            position: absolute;
            top: 8px;
            right: 10px;
            border: 0;
            background: transparent;
            font-size: 24px;
            line-height: 1;
            cursor: pointer;
        }

        .fpwp-popup label {
            display: block;
            margin-bottom: 6px;
        }

        .fpwp-popup input {
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 10px;
            padding: 8px;
        }

        .fpwp-popup button[type="submit"] {
            padding: 8px 12px;
            cursor: pointer;
        }

        .fpwp-message {
            margin: 12px 0 0;
            font-weight: 600;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const popup = document.getElementById('fpwp-popup');
            const form = document.getElementById('fpwp-form');
            const nameInput = document.getElementById('fpwp-name');
            const message = document.getElementById('fpwp-message');
            const closeButton = popup.querySelector('.fpwp-popup__close');

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                const name = nameInput.value.trim();

                if (!name) {
                    return;
                }

                message.textContent = 'Welcome, ' + name + '!';
                message.hidden = false;
            });

            closeButton.addEventListener('click', function () {
                popup.style.display = 'none';
            });
        });
    </script>
    <?php
}
```
