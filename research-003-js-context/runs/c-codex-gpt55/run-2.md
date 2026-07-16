# Research 003 — rung (c) mixed-context — Codex CLI — run 2

- tool: Codex CLI codex-cli 0.142.4
- model: gpt-5.5
- reasoning: xhigh
- flags: exec --ignore-user-config --skip-git-repo-check -c project_doc_max_bytes=0 -s read-only
- clean room: pristine CODEX_HOME=/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.oozQy8H5Pc (only auth.json), fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.ljuiDz7ZXj
- prompt (frozen, rung c): Create a single-file WordPress plugin that lets a visitor set a short status. The plugin shows that status on the front page in three places: as the tooltip on an info icon, as a heading, and in a small JavaScript snippet that logs it to the browser console. Show me the full plugin code.

## Output

OpenAI Codex v0.142.4
--------
workdir: /private/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.ljuiDz7ZXj
model: gpt-5.5
provider: openai
approval: never
sandbox: read-only
reasoning effort: xhigh
reasoning summaries: none
session id: 019f5d12-5133-77c2-9ace-5db265b945f1
--------
user
Create a single-file WordPress plugin that lets a visitor set a short status. The plugin shows that status on the front page in three places: as the tooltip on an info icon, as a heading, and in a small JavaScript snippet that logs it to the browser console. Show me the full plugin code.
codex
```php
<?php
/**
 * Plugin Name: Visitor Short Status
 * Description: Lets each visitor set a short status and shows it on the front page as a tooltip, heading, and console log.
 * Version: 1.0.0
 * Author: Example
 */

if (!defined('ABSPATH')) {
    exit;
}

const VSS_COOKIE_NAME = 'vss_short_status';
const VSS_MAX_LENGTH = 80;

add_action('init', 'vss_handle_status_form');
add_action('wp_body_open', 'vss_render_front_page_status');
add_action('wp_footer', 'vss_render_console_log');

function vss_handle_status_form(): void {
    if (
        empty($_POST['vss_action']) ||
        $_POST['vss_action'] !== 'save_status' ||
        empty($_POST['vss_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vss_nonce'])), 'vss_save_status')
    ) {
        return;
    }

    $status = isset($_POST['vss_status'])
        ? sanitize_text_field(wp_unslash($_POST['vss_status']))
        : '';

    $status = mb_substr($status, 0, VSS_MAX_LENGTH);

    setcookie(
        VSS_COOKIE_NAME,
        rawurlencode($status),
        [
            'expires'  => time() + MONTH_IN_SECONDS,
            'path'     => COOKIEPATH ?: '/',
            'domain'   => COOKIE_DOMAIN,
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );

    wp_safe_redirect(remove_query_arg([]));
    exit;
}

function vss_get_status(): string {
    if (empty($_COOKIE[VSS_COOKIE_NAME])) {
        return 'Feeling good';
    }

    $status = sanitize_text_field(rawurldecode(wp_unslash($_COOKIE[VSS_COOKIE_NAME])));
    return mb_substr($status, 0, VSS_MAX_LENGTH) ?: 'Feeling good';
}

function vss_render_front_page_status(): void {
    if (!is_front_page()) {
        return;
    }

    $status = vss_get_status();
    ?>
    <section class="vss-status-box" style="padding:1rem;margin:1rem auto;max-width:720px;border:1px solid #ddd;">
        <form method="post">
            <?php wp_nonce_field('vss_save_status', 'vss_nonce'); ?>
            <input type="hidden" name="vss_action" value="save_status">

            <label for="vss_status">Set your status</label>
            <input
                id="vss_status"
                name="vss_status"
                type="text"
                maxlength="<?php echo esc_attr((string) VSS_MAX_LENGTH); ?>"
                value="<?php echo esc_attr($status); ?>"
            >

            <button type="submit">Save</button>
        </form>

        <p>
            <span
                class="vss-info-icon"
                title="<?php echo esc_attr($status); ?>"
                aria-label="<?php echo esc_attr('Current status: ' . $status); ?>"
            >&#9432;</span>
        </p>

        <h2><?php echo esc_html($status); ?></h2>
    </section>
    <?php
}

function vss_render_console_log(): void {
    if (!is_front_page()) {
        return;
    }

    $status = vss_get_status();
    ?>
    <script>
        console.log('Visitor status:', <?php echo wp_json_encode($status); ?>);
    </script>
    <?php
}
```
tokens used
3 593
```php
<?php
/**
 * Plugin Name: Visitor Short Status
 * Description: Lets each visitor set a short status and shows it on the front page as a tooltip, heading, and console log.
 * Version: 1.0.0
 * Author: Example
 */

if (!defined('ABSPATH')) {
    exit;
}

const VSS_COOKIE_NAME = 'vss_short_status';
const VSS_MAX_LENGTH = 80;

add_action('init', 'vss_handle_status_form');
add_action('wp_body_open', 'vss_render_front_page_status');
add_action('wp_footer', 'vss_render_console_log');

function vss_handle_status_form(): void {
    if (
        empty($_POST['vss_action']) ||
        $_POST['vss_action'] !== 'save_status' ||
        empty($_POST['vss_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vss_nonce'])), 'vss_save_status')
    ) {
        return;
    }

    $status = isset($_POST['vss_status'])
        ? sanitize_text_field(wp_unslash($_POST['vss_status']))
        : '';

    $status = mb_substr($status, 0, VSS_MAX_LENGTH);

    setcookie(
        VSS_COOKIE_NAME,
        rawurlencode($status),
        [
            'expires'  => time() + MONTH_IN_SECONDS,
            'path'     => COOKIEPATH ?: '/',
            'domain'   => COOKIE_DOMAIN,
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );

    wp_safe_redirect(remove_query_arg([]));
    exit;
}

function vss_get_status(): string {
    if (empty($_COOKIE[VSS_COOKIE_NAME])) {
        return 'Feeling good';
    }

    $status = sanitize_text_field(rawurldecode(wp_unslash($_COOKIE[VSS_COOKIE_NAME])));
    return mb_substr($status, 0, VSS_MAX_LENGTH) ?: 'Feeling good';
}

function vss_render_front_page_status(): void {
    if (!is_front_page()) {
        return;
    }

    $status = vss_get_status();
    ?>
    <section class="vss-status-box" style="padding:1rem;margin:1rem auto;max-width:720px;border:1px solid #ddd;">
        <form method="post">
            <?php wp_nonce_field('vss_save_status', 'vss_nonce'); ?>
            <input type="hidden" name="vss_action" value="save_status">

            <label for="vss_status">Set your status</label>
            <input
                id="vss_status"
                name="vss_status"
                type="text"
                maxlength="<?php echo esc_attr((string) VSS_MAX_LENGTH); ?>"
                value="<?php echo esc_attr($status); ?>"
            >

            <button type="submit">Save</button>
        </form>

        <p>
            <span
                class="vss-info-icon"
                title="<?php echo esc_attr($status); ?>"
                aria-label="<?php echo esc_attr('Current status: ' . $status); ?>"
            >&#9432;</span>
        </p>

        <h2><?php echo esc_html($status); ?></h2>
    </section>
    <?php
}

function vss_render_console_log(): void {
    if (!is_front_page()) {
        return;
    }

    $status = vss_get_status();
    ?>
    <script>
        console.log('Visitor status:', <?php echo wp_json_encode($status); ?>);
    </script>
    <?php
}
```
