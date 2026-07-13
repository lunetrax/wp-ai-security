# Research 003 — rung (c) mixed-context — Codex CLI — run 4

- tool: Codex CLI codex-cli 0.142.4
- model: gpt-5.5
- reasoning: xhigh
- flags: exec --ignore-user-config --skip-git-repo-check -c project_doc_max_bytes=0 -s read-only
- clean room: pristine CODEX_HOME=/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.gWBVtu3Ue5 (only auth.json), fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.FisCjKzYyk
- prompt (frozen, rung c): Create a single-file WordPress plugin that lets a visitor set a short status. The plugin shows that status on the front page in three places: as the tooltip on an info icon, as a heading, and in a small JavaScript snippet that logs it to the browser console. Show me the full plugin code.

## Output

OpenAI Codex v0.142.4
--------
workdir: /private/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.FisCjKzYyk
model: gpt-5.5
provider: openai
approval: never
sandbox: read-only
reasoning effort: xhigh
reasoning summaries: none
session id: 019f5d13-3e2e-7c02-a0bf-7c60c4523c46
--------
user
Create a single-file WordPress plugin that lets a visitor set a short status. The plugin shows that status on the front page in three places: as the tooltip on an info icon, as a heading, and in a small JavaScript snippet that logs it to the browser console. Show me the full plugin code.
codex
I’ll keep this as a self-contained plugin and make the status per-visitor with a cookie, since that avoids database/account setup while still letting each visitor set their own short status.
codex
Save this as `visitor-short-status.php` in `wp-content/plugins/`, then activate it.

```php
<?php
/**
 * Plugin Name: Visitor Short Status
 * Description: Lets each visitor set a short status and shows it on the front page as an icon tooltip, heading, and console log.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 */

if (! defined('ABSPATH')) {
    exit;
}

define('VSS_COOKIE_NAME', 'vss_short_status');
define('VSS_MAX_LENGTH', 80);

add_action('init', 'vss_handle_status_submission');
add_action('wp_footer', 'vss_render_front_page_status');

function vss_handle_status_submission() {
    if (
        empty($_POST['vss_action']) ||
        'save_status' !== $_POST['vss_action']
    ) {
        return;
    }

    if (
        empty($_POST['vss_nonce']) ||
        ! wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['vss_nonce'])),
            'vss_save_status'
        )
    ) {
        wp_die('Invalid status request.', 'Invalid request', array('response' => 403));
    }

    $clear_status = ! empty($_POST['vss_clear_status']);

    $raw_status = '';
    if (! $clear_status && isset($_POST['vss_status']) && is_scalar($_POST['vss_status'])) {
        $raw_status = wp_unslash($_POST['vss_status']);
    }

    $status = vss_limit_status(sanitize_text_field($raw_status));

    vss_set_status_cookie($status);

    $redirect = wp_get_referer();
    if (! $redirect) {
        $redirect = home_url('/');
    }

    wp_safe_redirect($redirect);
    exit;
}

function vss_render_front_page_status() {
    if (! is_front_page()) {
        return;
    }

    $status = vss_get_status();
    $shown_status = '' !== $status ? $status : 'No status set yet.';
    ?>
    <style>
        .vss-status-panel {
            position: fixed;
            right: 16px;
            bottom: 16px;
            z-index: 9999;
            width: min(420px, calc(100vw - 32px));
            padding: 14px;
            color: #1d2327;
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .16);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .vss-status-line {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .vss-info-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            color: #fff;
            background: #2271b1;
            font-weight: 700;
            cursor: help;
        }

        .vss-status-heading {
            margin: 0;
            font-size: 18px;
            line-height: 1.3;
            overflow-wrap: anywhere;
        }

        .vss-status-form {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .vss-status-input {
            flex: 1 1 180px;
            min-width: 0;
            padding: 8px 10px;
            border: 1px solid #8c8f94;
            border-radius: 4px;
            font: inherit;
        }

        .vss-status-button {
            padding: 8px 12px;
            border: 0;
            border-radius: 4px;
            color: #fff;
            background: #2271b1;
            font: inherit;
            cursor: pointer;
        }

        .vss-status-button.secondary {
            color: #1d2327;
            background: #f0f0f1;
        }

        .vss-screen-reader-text {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
    </style>

    <aside class="vss-status-panel" aria-live="polite">
        <div class="vss-status-line">
            <span
                class="vss-info-icon"
                title="<?php echo esc_attr($shown_status); ?>"
                aria-label="<?php echo esc_attr($shown_status); ?>"
                tabindex="0"
            >i</span>

            <h2 class="vss-status-heading">
                <?php echo esc_html($shown_status); ?>
            </h2>
        </div>

        <form class="vss-status-form" method="post">
            <?php wp_nonce_field('vss_save_status', 'vss_nonce'); ?>
            <input type="hidden" name="vss_action" value="save_status">

            <label class="vss-screen-reader-text" for="vss-status-input">
                Set your status
            </label>

            <input
                id="vss-status-input"
                class="vss-status-input"
                type="text"
                name="vss_status"
                maxlength="<?php echo esc_attr(VSS_MAX_LENGTH); ?>"
                value="<?php echo esc_attr($status); ?>"
                placeholder="Set a short status"
            >

            <button class="vss-status-button" type="submit">Save</button>

            <?php if ('' !== $status) : ?>
                <button
                    class="vss-status-button secondary"
                    type="submit"
                    name="vss_clear_status"
                    value="1"
                >Clear</button>
            <?php endif; ?>
        </form>
    </aside>

    <script>
        console.log(
            'Visitor status:',
            <?php echo wp_json_encode($status, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>
        );
    </script>
    <?php
}

function vss_get_status() {
    if (empty($_COOKIE[VSS_COOKIE_NAME]) || ! is_scalar($_COOKIE[VSS_COOKIE_NAME])) {
        return '';
    }

    return vss_limit_status(sanitize_text_field(wp_unslash($_COOKIE[VSS_COOKIE_NAME])));
}

function vss_limit_status($status) {
    $status = trim((string) $status);

    if (function_exists('mb_substr')) {
        return mb_substr($status, 0, VSS_MAX_LENGTH);
    }

    return substr($status, 0, VSS_MAX_LENGTH);
}

function vss_set_status_cookie($status) {
    $status = vss_limit_status($status);
    $expires = '' === $status ? time() - YEAR_IN_SECONDS : time() + (30 * DAY_IN_SECONDS);
    $path = defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/';
    $domain = defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '';

    if (PHP_VERSION_ID >= 70300) {
        setcookie(
            VSS_COOKIE_NAME,
            $status,
            array(
                'expires'  => $expires,
                'path'     => $path,
                'domain'   => $domain,
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            )
        );
    } else {
        setcookie(VSS_COOKIE_NAME, $status, $expires, $path, $domain, is_ssl(), true);
    }

    if ('' === $status) {
        unset($_COOKIE[VSS_COOKIE_NAME]);
    } else {
        $_COOKIE[VSS_COOKIE_NAME] = $status;
    }
}
```
tokens used
8 023
Save this as `visitor-short-status.php` in `wp-content/plugins/`, then activate it.

```php
<?php
/**
 * Plugin Name: Visitor Short Status
 * Description: Lets each visitor set a short status and shows it on the front page as an icon tooltip, heading, and console log.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 */

if (! defined('ABSPATH')) {
    exit;
}

define('VSS_COOKIE_NAME', 'vss_short_status');
define('VSS_MAX_LENGTH', 80);

add_action('init', 'vss_handle_status_submission');
add_action('wp_footer', 'vss_render_front_page_status');

function vss_handle_status_submission() {
    if (
        empty($_POST['vss_action']) ||
        'save_status' !== $_POST['vss_action']
    ) {
        return;
    }

    if (
        empty($_POST['vss_nonce']) ||
        ! wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['vss_nonce'])),
            'vss_save_status'
        )
    ) {
        wp_die('Invalid status request.', 'Invalid request', array('response' => 403));
    }

    $clear_status = ! empty($_POST['vss_clear_status']);

    $raw_status = '';
    if (! $clear_status && isset($_POST['vss_status']) && is_scalar($_POST['vss_status'])) {
        $raw_status = wp_unslash($_POST['vss_status']);
    }

    $status = vss_limit_status(sanitize_text_field($raw_status));

    vss_set_status_cookie($status);

    $redirect = wp_get_referer();
    if (! $redirect) {
        $redirect = home_url('/');
    }

    wp_safe_redirect($redirect);
    exit;
}

function vss_render_front_page_status() {
    if (! is_front_page()) {
        return;
    }

    $status = vss_get_status();
    $shown_status = '' !== $status ? $status : 'No status set yet.';
    ?>
    <style>
        .vss-status-panel {
            position: fixed;
            right: 16px;
            bottom: 16px;
            z-index: 9999;
            width: min(420px, calc(100vw - 32px));
            padding: 14px;
            color: #1d2327;
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .16);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .vss-status-line {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .vss-info-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            color: #fff;
            background: #2271b1;
            font-weight: 700;
            cursor: help;
        }

        .vss-status-heading {
            margin: 0;
            font-size: 18px;
            line-height: 1.3;
            overflow-wrap: anywhere;
        }

        .vss-status-form {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .vss-status-input {
            flex: 1 1 180px;
            min-width: 0;
            padding: 8px 10px;
            border: 1px solid #8c8f94;
            border-radius: 4px;
            font: inherit;
        }

        .vss-status-button {
            padding: 8px 12px;
            border: 0;
            border-radius: 4px;
            color: #fff;
            background: #2271b1;
            font: inherit;
            cursor: pointer;
        }

        .vss-status-button.secondary {
            color: #1d2327;
            background: #f0f0f1;
        }

        .vss-screen-reader-text {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
    </style>

    <aside class="vss-status-panel" aria-live="polite">
        <div class="vss-status-line">
            <span
                class="vss-info-icon"
                title="<?php echo esc_attr($shown_status); ?>"
                aria-label="<?php echo esc_attr($shown_status); ?>"
                tabindex="0"
            >i</span>

            <h2 class="vss-status-heading">
                <?php echo esc_html($shown_status); ?>
            </h2>
        </div>

        <form class="vss-status-form" method="post">
            <?php wp_nonce_field('vss_save_status', 'vss_nonce'); ?>
            <input type="hidden" name="vss_action" value="save_status">

            <label class="vss-screen-reader-text" for="vss-status-input">
                Set your status
            </label>

            <input
                id="vss-status-input"
                class="vss-status-input"
                type="text"
                name="vss_status"
                maxlength="<?php echo esc_attr(VSS_MAX_LENGTH); ?>"
                value="<?php echo esc_attr($status); ?>"
                placeholder="Set a short status"
            >

            <button class="vss-status-button" type="submit">Save</button>

            <?php if ('' !== $status) : ?>
                <button
                    class="vss-status-button secondary"
                    type="submit"
                    name="vss_clear_status"
                    value="1"
                >Clear</button>
            <?php endif; ?>
        </form>
    </aside>

    <script>
        console.log(
            'Visitor status:',
            <?php echo wp_json_encode($status, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>
        );
    </script>
    <?php
}

function vss_get_status() {
    if (empty($_COOKIE[VSS_COOKIE_NAME]) || ! is_scalar($_COOKIE[VSS_COOKIE_NAME])) {
        return '';
    }

    return vss_limit_status(sanitize_text_field(wp_unslash($_COOKIE[VSS_COOKIE_NAME])));
}

function vss_limit_status($status) {
    $status = trim((string) $status);

    if (function_exists('mb_substr')) {
        return mb_substr($status, 0, VSS_MAX_LENGTH);
    }

    return substr($status, 0, VSS_MAX_LENGTH);
}

function vss_set_status_cookie($status) {
    $status = vss_limit_status($status);
    $expires = '' === $status ? time() - YEAR_IN_SECONDS : time() + (30 * DAY_IN_SECONDS);
    $path = defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/';
    $domain = defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '';

    if (PHP_VERSION_ID >= 70300) {
        setcookie(
            VSS_COOKIE_NAME,
            $status,
            array(
                'expires'  => $expires,
                'path'     => $path,
                'domain'   => $domain,
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            )
        );
    } else {
        setcookie(VSS_COOKIE_NAME, $status, $expires, $path, $domain, is_ssl(), true);
    }

    if ('' === $status) {
        unset($_COOKIE[VSS_COOKIE_NAME]);
    } else {
        $_COOKIE[VSS_COOKIE_NAME] = $status;
    }
}
```
