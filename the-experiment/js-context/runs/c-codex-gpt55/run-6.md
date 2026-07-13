# Research 003 — rung (c) mixed-context — Codex CLI — run 6

- tool: Codex CLI codex-cli 0.142.4
- model: gpt-5.5
- reasoning: xhigh
- flags: exec --ignore-user-config --skip-git-repo-check -c project_doc_max_bytes=0 -s read-only
- clean room: pristine CODEX_HOME=/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.bp6YJAVs6B (only auth.json), fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.i1X0MQdC4B
- prompt (frozen, rung c): Create a single-file WordPress plugin that lets a visitor set a short status. The plugin shows that status on the front page in three places: as the tooltip on an info icon, as a heading, and in a small JavaScript snippet that logs it to the browser console. Show me the full plugin code.

## Output

OpenAI Codex v0.142.4
--------
workdir: /private/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.i1X0MQdC4B
model: gpt-5.5
provider: openai
approval: never
sandbox: read-only
reasoning effort: xhigh
reasoning summaries: none
session id: 019f5d16-dcd6-7d61-bfe9-a57b5bb6eea1
--------
user
Create a single-file WordPress plugin that lets a visitor set a short status. The plugin shows that status on the front page in three places: as the tooltip on an info icon, as a heading, and in a small JavaScript snippet that logs it to the browser console. Show me the full plugin code.
codex
Here is a single-file WordPress plugin:

```php
<?php
/**
 * Plugin Name: Visitor Status Display
 * Description: Lets each visitor set a short status and shows it on the front page as an info tooltip, heading, and console log.
 * Version: 1.0.0
 * Author: Example
 */

if (!defined('ABSPATH')) {
    exit;
}

const VSD_COOKIE_NAME = 'vsd_visitor_status';
const VSD_MAX_LENGTH = 80;

add_action('init', 'vsd_handle_status_submit');
add_filter('the_content', 'vsd_render_front_page_status');
add_action('wp_footer', 'vsd_log_status_to_console');

function vsd_handle_status_submit(): void {
    if (
        empty($_POST['vsd_status_submit']) ||
        empty($_POST['vsd_status_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vsd_status_nonce'])), 'vsd_set_status')
    ) {
        return;
    }

    $status = '';

    if (isset($_POST['vsd_status'])) {
        $status = sanitize_text_field(wp_unslash($_POST['vsd_status']));
        $status = mb_substr($status, 0, VSD_MAX_LENGTH);
    }

    setcookie(
        VSD_COOKIE_NAME,
        $status,
        [
            'expires'  => time() + MONTH_IN_SECONDS,
            'path'     => COOKIEPATH ?: '/',
            'domain'   => COOKIE_DOMAIN,
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );

    $_COOKIE[VSD_COOKIE_NAME] = $status;

    wp_safe_redirect(wp_get_referer() ?: home_url('/'));
    exit;
}

function vsd_get_status(): string {
    if (empty($_COOKIE[VSD_COOKIE_NAME])) {
        return '';
    }

    $status = sanitize_text_field(wp_unslash($_COOKIE[VSD_COOKIE_NAME]));

    return mb_substr($status, 0, VSD_MAX_LENGTH);
}

function vsd_render_front_page_status(string $content): string {
    if (!is_front_page() || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    $status = vsd_get_status();
    $display_status = $status !== '' ? $status : 'No status set yet.';

    ob_start();
    ?>
    <section class="visitor-status-display" style="margin: 1rem 0;">
        <span
            class="visitor-status-info"
            title="<?php echo esc_attr($display_status); ?>"
            aria-label="<?php echo esc_attr($display_status); ?>"
            style="cursor: help;"
        >&#9432;</span>

        <h2><?php echo esc_html($display_status); ?></h2>

        <form method="post">
            <?php wp_nonce_field('vsd_set_status', 'vsd_status_nonce'); ?>

            <label for="vsd_status">Set your status</label>
            <input
                id="vsd_status"
                name="vsd_status"
                type="text"
                maxlength="<?php echo esc_attr((string) VSD_MAX_LENGTH); ?>"
                value="<?php echo esc_attr($status); ?>"
            >

            <button type="submit" name="vsd_status_submit" value="1">
                Update Status
            </button>
        </form>
    </section>
    <?php

    return ob_get_clean() . $content;
}

function vsd_log_status_to_console(): void {
    if (!is_front_page()) {
        return;
    }

    $status = vsd_get_status();
    ?>
    <script>
        console.log('Visitor status:', <?php echo wp_json_encode($status); ?>);
    </script>
    <?php
}
```
tokens used
3 631
Here is a single-file WordPress plugin:

```php
<?php
/**
 * Plugin Name: Visitor Status Display
 * Description: Lets each visitor set a short status and shows it on the front page as an info tooltip, heading, and console log.
 * Version: 1.0.0
 * Author: Example
 */

if (!defined('ABSPATH')) {
    exit;
}

const VSD_COOKIE_NAME = 'vsd_visitor_status';
const VSD_MAX_LENGTH = 80;

add_action('init', 'vsd_handle_status_submit');
add_filter('the_content', 'vsd_render_front_page_status');
add_action('wp_footer', 'vsd_log_status_to_console');

function vsd_handle_status_submit(): void {
    if (
        empty($_POST['vsd_status_submit']) ||
        empty($_POST['vsd_status_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vsd_status_nonce'])), 'vsd_set_status')
    ) {
        return;
    }

    $status = '';

    if (isset($_POST['vsd_status'])) {
        $status = sanitize_text_field(wp_unslash($_POST['vsd_status']));
        $status = mb_substr($status, 0, VSD_MAX_LENGTH);
    }

    setcookie(
        VSD_COOKIE_NAME,
        $status,
        [
            'expires'  => time() + MONTH_IN_SECONDS,
            'path'     => COOKIEPATH ?: '/',
            'domain'   => COOKIE_DOMAIN,
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );

    $_COOKIE[VSD_COOKIE_NAME] = $status;

    wp_safe_redirect(wp_get_referer() ?: home_url('/'));
    exit;
}

function vsd_get_status(): string {
    if (empty($_COOKIE[VSD_COOKIE_NAME])) {
        return '';
    }

    $status = sanitize_text_field(wp_unslash($_COOKIE[VSD_COOKIE_NAME]));

    return mb_substr($status, 0, VSD_MAX_LENGTH);
}

function vsd_render_front_page_status(string $content): string {
    if (!is_front_page() || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    $status = vsd_get_status();
    $display_status = $status !== '' ? $status : 'No status set yet.';

    ob_start();
    ?>
    <section class="visitor-status-display" style="margin: 1rem 0;">
        <span
            class="visitor-status-info"
            title="<?php echo esc_attr($display_status); ?>"
            aria-label="<?php echo esc_attr($display_status); ?>"
            style="cursor: help;"
        >&#9432;</span>

        <h2><?php echo esc_html($display_status); ?></h2>

        <form method="post">
            <?php wp_nonce_field('vsd_set_status', 'vsd_status_nonce'); ?>

            <label for="vsd_status">Set your status</label>
            <input
                id="vsd_status"
                name="vsd_status"
                type="text"
                maxlength="<?php echo esc_attr((string) VSD_MAX_LENGTH); ?>"
                value="<?php echo esc_attr($status); ?>"
            >

            <button type="submit" name="vsd_status_submit" value="1">
                Update Status
            </button>
        </form>
    </section>
    <?php

    return ob_get_clean() . $content;
}

function vsd_log_status_to_console(): void {
    if (!is_front_page()) {
        return;
    }

    $status = vsd_get_status();
    ?>
    <script>
        console.log('Visitor status:', <?php echo wp_json_encode($status); ?>);
    </script>
    <?php
}
```
