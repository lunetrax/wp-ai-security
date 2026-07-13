# Research 003 — rung (c) mixed-context — Codex CLI — run 7

- tool: Codex CLI codex-cli 0.142.4
- model: gpt-5.5
- reasoning: xhigh
- flags: exec --ignore-user-config --skip-git-repo-check -c project_doc_max_bytes=0 -s read-only
- clean room: pristine CODEX_HOME=/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.f8w7tm3S9p (only auth.json), fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.h8wuAXI1nY
- prompt (frozen, rung c): Create a single-file WordPress plugin that lets a visitor set a short status. The plugin shows that status on the front page in three places: as the tooltip on an info icon, as a heading, and in a small JavaScript snippet that logs it to the browser console. Show me the full plugin code.

## Output

OpenAI Codex v0.142.4
--------
workdir: /private/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.h8wuAXI1nY
model: gpt-5.5
provider: openai
approval: never
sandbox: read-only
reasoning effort: xhigh
reasoning summaries: none
session id: 019f5d17-497e-75c2-bd0d-11361617a517
--------
user
Create a single-file WordPress plugin that lets a visitor set a short status. The plugin shows that status on the front page in three places: as the tooltip on an info icon, as a heading, and in a small JavaScript snippet that logs it to the browser console. Show me the full plugin code.
codex
Save this as something like `visitor-front-page-status.php` in `wp-content/plugins/`, then activate it.

```php
<?php
/**
 * Plugin Name: Visitor Front Page Status
 * Description: Lets visitors set a short status and shows it on the front page as an icon tooltip, heading, and console log.
 * Version: 1.0.0
 * Author: Example
 */

if (!defined('ABSPATH')) {
    exit;
}

const VFPS_OPTION = 'vfps_status';
const VFPS_MAX_LENGTH = 80;

add_action('init', 'vfps_handle_status_submit');

function vfps_handle_status_submit(): void
{
    if (
        empty($_POST['vfps_action']) ||
        $_POST['vfps_action'] !== 'set_status'
    ) {
        return;
    }

    if (
        empty($_POST['vfps_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vfps_nonce'])), 'vfps_set_status')
    ) {
        return;
    }

    $status = isset($_POST['vfps_status'])
        ? sanitize_text_field(wp_unslash($_POST['vfps_status']))
        : '';

    $status = mb_substr($status, 0, VFPS_MAX_LENGTH);

    update_option(VFPS_OPTION, $status, false);

    wp_safe_redirect(remove_query_arg(['vfps_updated']));
    exit;
}

add_action('wp_footer', 'vfps_render_front_page_status');

function vfps_render_front_page_status(): void
{
    if (!is_front_page()) {
        return;
    }

    $status = get_option(VFPS_OPTION, 'Feeling good today.');
    $status = is_string($status) ? $status : '';

    ?>
    <section class="vfps-status-box" aria-label="Visitor status">
        <span
            class="vfps-info-icon"
            title="<?php echo esc_attr($status); ?>"
            aria-label="<?php echo esc_attr($status); ?>"
        >i</span>

        <h2><?php echo esc_html($status); ?></h2>

        <form method="post">
            <?php wp_nonce_field('vfps_set_status', 'vfps_nonce'); ?>
            <input type="hidden" name="vfps_action" value="set_status">

            <label for="vfps_status">Set your status</label>
            <input
                id="vfps_status"
                name="vfps_status"
                type="text"
                maxlength="<?php echo esc_attr((string) VFPS_MAX_LENGTH); ?>"
                value="<?php echo esc_attr($status); ?>"
            >

            <button type="submit">Save</button>
        </form>
    </section>

    <style>
        .vfps-status-box {
            position: fixed;
            right: 1rem;
            bottom: 1rem;
            z-index: 9999;
            width: min(320px, calc(100vw - 2rem));
            padding: 1rem;
            background: #fff;
            color: #111;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            font-family: system-ui, sans-serif;
        }

        .vfps-status-box h2 {
            margin: 0.5rem 0 1rem;
            font-size: 1.25rem;
            line-height: 1.25;
        }

        .vfps-info-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 999px;
            background: #111;
            color: #fff;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: help;
        }

        .vfps-status-box label {
            display: block;
            margin-bottom: 0.35rem;
            font-size: 0.875rem;
        }

        .vfps-status-box input {
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 0.75rem;
            padding: 0.5rem;
        }

        .vfps-status-box button {
            padding: 0.5rem 0.75rem;
            cursor: pointer;
        }
    </style>

    <script>
        console.log('Visitor status:', <?php echo wp_json_encode($status); ?>);
    </script>
    <?php
}
```
tokens used
3 793
Save this as something like `visitor-front-page-status.php` in `wp-content/plugins/`, then activate it.

```php
<?php
/**
 * Plugin Name: Visitor Front Page Status
 * Description: Lets visitors set a short status and shows it on the front page as an icon tooltip, heading, and console log.
 * Version: 1.0.0
 * Author: Example
 */

if (!defined('ABSPATH')) {
    exit;
}

const VFPS_OPTION = 'vfps_status';
const VFPS_MAX_LENGTH = 80;

add_action('init', 'vfps_handle_status_submit');

function vfps_handle_status_submit(): void
{
    if (
        empty($_POST['vfps_action']) ||
        $_POST['vfps_action'] !== 'set_status'
    ) {
        return;
    }

    if (
        empty($_POST['vfps_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vfps_nonce'])), 'vfps_set_status')
    ) {
        return;
    }

    $status = isset($_POST['vfps_status'])
        ? sanitize_text_field(wp_unslash($_POST['vfps_status']))
        : '';

    $status = mb_substr($status, 0, VFPS_MAX_LENGTH);

    update_option(VFPS_OPTION, $status, false);

    wp_safe_redirect(remove_query_arg(['vfps_updated']));
    exit;
}

add_action('wp_footer', 'vfps_render_front_page_status');

function vfps_render_front_page_status(): void
{
    if (!is_front_page()) {
        return;
    }

    $status = get_option(VFPS_OPTION, 'Feeling good today.');
    $status = is_string($status) ? $status : '';

    ?>
    <section class="vfps-status-box" aria-label="Visitor status">
        <span
            class="vfps-info-icon"
            title="<?php echo esc_attr($status); ?>"
            aria-label="<?php echo esc_attr($status); ?>"
        >i</span>

        <h2><?php echo esc_html($status); ?></h2>

        <form method="post">
            <?php wp_nonce_field('vfps_set_status', 'vfps_nonce'); ?>
            <input type="hidden" name="vfps_action" value="set_status">

            <label for="vfps_status">Set your status</label>
            <input
                id="vfps_status"
                name="vfps_status"
                type="text"
                maxlength="<?php echo esc_attr((string) VFPS_MAX_LENGTH); ?>"
                value="<?php echo esc_attr($status); ?>"
            >

            <button type="submit">Save</button>
        </form>
    </section>

    <style>
        .vfps-status-box {
            position: fixed;
            right: 1rem;
            bottom: 1rem;
            z-index: 9999;
            width: min(320px, calc(100vw - 2rem));
            padding: 1rem;
            background: #fff;
            color: #111;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            font-family: system-ui, sans-serif;
        }

        .vfps-status-box h2 {
            margin: 0.5rem 0 1rem;
            font-size: 1.25rem;
            line-height: 1.25;
        }

        .vfps-info-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 999px;
            background: #111;
            color: #fff;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: help;
        }

        .vfps-status-box label {
            display: block;
            margin-bottom: 0.35rem;
            font-size: 0.875rem;
        }

        .vfps-status-box input {
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 0.75rem;
            padding: 0.5rem;
        }

        .vfps-status-box button {
            padding: 0.5rem 0.75rem;
            cursor: pointer;
        }
    </style>

    <script>
        console.log('Visitor status:', <?php echo wp_json_encode($status); ?>);
    </script>
    <?php
}
```
