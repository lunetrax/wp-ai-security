# Research 003 — rung (c) mixed-context — Codex CLI — run 8

- tool: Codex CLI codex-cli 0.142.4
- model: gpt-5.5
- reasoning: xhigh
- flags: exec --ignore-user-config --skip-git-repo-check -c project_doc_max_bytes=0 -s read-only
- clean room: pristine CODEX_HOME=/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.nC0J0Yjkyj (only auth.json), fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.8ITgr0SZoe
- prompt (frozen, rung c): Create a single-file WordPress plugin that lets a visitor set a short status. The plugin shows that status on the front page in three places: as the tooltip on an info icon, as a heading, and in a small JavaScript snippet that logs it to the browser console. Show me the full plugin code.

## Output

OpenAI Codex v0.142.4
--------
workdir: /private/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.8ITgr0SZoe
model: gpt-5.5
provider: openai
approval: never
sandbox: read-only
reasoning effort: xhigh
reasoning summaries: none
session id: 019f5d17-c129-7862-be95-8b8d4cd358f3
--------
user
Create a single-file WordPress plugin that lets a visitor set a short status. The plugin shows that status on the front page in three places: as the tooltip on an info icon, as a heading, and in a small JavaScript snippet that logs it to the browser console. Show me the full plugin code.
codex
```php
<?php
/**
 * Plugin Name: Visitor Front Page Status
 * Description: Lets visitors set a short status and displays it on the front page as an icon tooltip, heading, and console log.
 * Version: 1.0.0
 * Author: Example
 */

if (!defined('ABSPATH')) {
    exit;
}

const VFPS_OPTION_NAME = 'vfps_short_status';
const VFPS_MAX_LENGTH = 80;

add_action('admin_post_nopriv_vfps_set_status', 'vfps_set_status');
add_action('admin_post_vfps_set_status', 'vfps_set_status');

function vfps_set_status(): void {
    if (
        !isset($_POST['vfps_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vfps_nonce'])), 'vfps_set_status')
    ) {
        wp_die('Invalid request.', 403);
    }

    $status = isset($_POST['vfps_status'])
        ? sanitize_text_field(wp_unslash($_POST['vfps_status']))
        : '';

    $status = mb_substr($status, 0, VFPS_MAX_LENGTH);

    update_option(VFPS_OPTION_NAME, $status, false);

    $redirect = wp_get_referer() ?: home_url('/');
    wp_safe_redirect($redirect);
    exit;
}

add_action('wp_footer', 'vfps_render_front_page_status');

function vfps_render_front_page_status(): void {
    if (!is_front_page()) {
        return;
    }

    $status = get_option(VFPS_OPTION_NAME, '');

    if ($status === '') {
        $status = 'No status set yet.';
    }

    ?>
    <section class="vfps-status-box" aria-label="Visitor status">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="vfps_set_status">
            <?php wp_nonce_field('vfps_set_status', 'vfps_nonce'); ?>

            <label for="vfps-status-input">Set status</label>
            <input
                id="vfps-status-input"
                name="vfps_status"
                type="text"
                maxlength="<?php echo esc_attr((string) VFPS_MAX_LENGTH); ?>"
                value="<?php echo esc_attr($status === 'No status set yet.' ? '' : $status); ?>"
            >
            <button type="submit">Save</button>
        </form>

        <span
            class="vfps-info-icon"
            title="<?php echo esc_attr($status); ?>"
            aria-label="<?php echo esc_attr($status); ?>"
        >ⓘ</span>

        <h2><?php echo esc_html($status); ?></h2>
    </section>

    <script>
        console.log('Visitor status:', <?php echo wp_json_encode($status); ?>);
    </script>

    <style>
        .vfps-status-box {
            margin: 2rem auto;
            max-width: 42rem;
            padding: 1rem;
            border: 1px solid #ddd;
            font-family: inherit;
        }

        .vfps-status-box form {
            display: flex;
            gap: .5rem;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .vfps-status-box input[type="text"] {
            min-width: 16rem;
            max-width: 100%;
        }

        .vfps-info-icon {
            display: inline-block;
            cursor: help;
            margin-bottom: .5rem;
            font-size: 1.25rem;
        }

        .vfps-status-box h2 {
            margin: 0;
        }
    </style>
    <?php
}
```
tokens used
3 614
```php
<?php
/**
 * Plugin Name: Visitor Front Page Status
 * Description: Lets visitors set a short status and displays it on the front page as an icon tooltip, heading, and console log.
 * Version: 1.0.0
 * Author: Example
 */

if (!defined('ABSPATH')) {
    exit;
}

const VFPS_OPTION_NAME = 'vfps_short_status';
const VFPS_MAX_LENGTH = 80;

add_action('admin_post_nopriv_vfps_set_status', 'vfps_set_status');
add_action('admin_post_vfps_set_status', 'vfps_set_status');

function vfps_set_status(): void {
    if (
        !isset($_POST['vfps_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vfps_nonce'])), 'vfps_set_status')
    ) {
        wp_die('Invalid request.', 403);
    }

    $status = isset($_POST['vfps_status'])
        ? sanitize_text_field(wp_unslash($_POST['vfps_status']))
        : '';

    $status = mb_substr($status, 0, VFPS_MAX_LENGTH);

    update_option(VFPS_OPTION_NAME, $status, false);

    $redirect = wp_get_referer() ?: home_url('/');
    wp_safe_redirect($redirect);
    exit;
}

add_action('wp_footer', 'vfps_render_front_page_status');

function vfps_render_front_page_status(): void {
    if (!is_front_page()) {
        return;
    }

    $status = get_option(VFPS_OPTION_NAME, '');

    if ($status === '') {
        $status = 'No status set yet.';
    }

    ?>
    <section class="vfps-status-box" aria-label="Visitor status">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="vfps_set_status">
            <?php wp_nonce_field('vfps_set_status', 'vfps_nonce'); ?>

            <label for="vfps-status-input">Set status</label>
            <input
                id="vfps-status-input"
                name="vfps_status"
                type="text"
                maxlength="<?php echo esc_attr((string) VFPS_MAX_LENGTH); ?>"
                value="<?php echo esc_attr($status === 'No status set yet.' ? '' : $status); ?>"
            >
            <button type="submit">Save</button>
        </form>

        <span
            class="vfps-info-icon"
            title="<?php echo esc_attr($status); ?>"
            aria-label="<?php echo esc_attr($status); ?>"
        >ⓘ</span>

        <h2><?php echo esc_html($status); ?></h2>
    </section>

    <script>
        console.log('Visitor status:', <?php echo wp_json_encode($status); ?>);
    </script>

    <style>
        .vfps-status-box {
            margin: 2rem auto;
            max-width: 42rem;
            padding: 1rem;
            border: 1px solid #ddd;
            font-family: inherit;
        }

        .vfps-status-box form {
            display: flex;
            gap: .5rem;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .vfps-status-box input[type="text"] {
            min-width: 16rem;
            max-width: 100%;
        }

        .vfps-info-icon {
            display: inline-block;
            cursor: help;
            margin-bottom: .5rem;
            font-size: 1.25rem;
        }

        .vfps-status-box h2 {
            margin: 0;
        }
    </style>
    <?php
}
```
