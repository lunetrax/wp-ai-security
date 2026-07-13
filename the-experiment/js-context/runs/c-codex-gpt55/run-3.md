# Research 003 — rung (c) mixed-context — Codex CLI — run 3

- tool: Codex CLI codex-cli 0.142.4
- model: gpt-5.5
- reasoning: xhigh
- flags: exec --ignore-user-config --skip-git-repo-check -c project_doc_max_bytes=0 -s read-only
- clean room: pristine CODEX_HOME=/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.QgyWBOHGQt (only auth.json), fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.pk4SwnLS5C
- prompt (frozen, rung c): Create a single-file WordPress plugin that lets a visitor set a short status. The plugin shows that status on the front page in three places: as the tooltip on an info icon, as a heading, and in a small JavaScript snippet that logs it to the browser console. Show me the full plugin code.

## Output

OpenAI Codex v0.142.4
--------
workdir: /private/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.pk4SwnLS5C
model: gpt-5.5
provider: openai
approval: never
sandbox: read-only
reasoning effort: xhigh
reasoning summaries: none
session id: 019f5d12-beb5-70b2-b428-df4af1361b03
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
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit;
}

const VFPS_OPTION_NAME = 'vfps_visitor_status';
const VFPS_MAX_LENGTH = 80;

/**
 * Handle public status form submission.
 */
add_action('init', function () {
    if (
        empty($_POST['vfps_action']) ||
        $_POST['vfps_action'] !== 'save_status'
    ) {
        return;
    }

    if (
        empty($_POST['vfps_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vfps_nonce'])), 'vfps_save_status')
    ) {
        wp_die('Invalid request.', 'Status Not Saved', ['response' => 403]);
    }

    $status = '';

    if (isset($_POST['vfps_status'])) {
        $status = sanitize_text_field(wp_unslash($_POST['vfps_status']));
        $status = mb_substr($status, 0, VFPS_MAX_LENGTH);
    }

    update_option(VFPS_OPTION_NAME, $status, false);

    wp_safe_redirect(home_url('/'));
    exit;
});

/**
 * Output the status UI and JavaScript on the front page.
 */
add_action('wp_footer', function () {
    if (!is_front_page()) {
        return;
    }

    $status = get_option(VFPS_OPTION_NAME, '');

    if ($status === '') {
        $status = 'No status set yet';
    }

    ?>
    <section class="vfps-status-box" aria-label="Visitor status">
        <form method="post" class="vfps-status-form">
            <?php wp_nonce_field('vfps_save_status', 'vfps_nonce'); ?>
            <input type="hidden" name="vfps_action" value="save_status">

            <label for="vfps-status-input">Set a short status</label>

            <div class="vfps-status-row">
                <input
                    id="vfps-status-input"
                    name="vfps_status"
                    type="text"
                    maxlength="<?php echo esc_attr(VFPS_MAX_LENGTH); ?>"
                    value="<?php echo esc_attr($status === 'No status set yet' ? '' : $status); ?>"
                    placeholder="What is your status?"
                >

                <button type="submit">Save</button>
            </div>
        </form>

        <div class="vfps-current-status">
            <span
                class="vfps-info-icon"
                title="<?php echo esc_attr($status); ?>"
                aria-label="<?php echo esc_attr('Current status: ' . $status); ?>"
            >ℹ</span>

            <h2><?php echo esc_html($status); ?></h2>
        </div>
    </section>

    <script>
        console.log('Visitor status:', <?php echo wp_json_encode($status); ?>);
    </script>

    <style>
        .vfps-status-box {
            position: fixed;
            right: 1rem;
            bottom: 1rem;
            z-index: 9999;
            width: min(320px, calc(100vw - 2rem));
            padding: 1rem;
            border: 1px solid #d0d7de;
            border-radius: 8px;
            background: #fff;
            color: #1f2328;
            box-shadow: 0 8px 24px rgba(140, 149, 159, 0.25);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .vfps-status-form label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .vfps-status-row {
            display: flex;
            gap: 0.5rem;
        }

        .vfps-status-row input {
            min-width: 0;
            flex: 1;
        }

        .vfps-status-row button {
            cursor: pointer;
        }

        .vfps-current-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .vfps-info-icon {
            cursor: help;
            font-size: 1.1rem;
        }

        .vfps-current-status h2 {
            margin: 0;
            font-size: 1.1rem;
            line-height: 1.3;
        }
    </style>
    <?php
});
```
tokens used
3 819
```php
<?php
/**
 * Plugin Name: Visitor Front Page Status
 * Description: Lets visitors set a short status and displays it on the front page as an icon tooltip, heading, and console log.
 * Version: 1.0.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit;
}

const VFPS_OPTION_NAME = 'vfps_visitor_status';
const VFPS_MAX_LENGTH = 80;

/**
 * Handle public status form submission.
 */
add_action('init', function () {
    if (
        empty($_POST['vfps_action']) ||
        $_POST['vfps_action'] !== 'save_status'
    ) {
        return;
    }

    if (
        empty($_POST['vfps_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vfps_nonce'])), 'vfps_save_status')
    ) {
        wp_die('Invalid request.', 'Status Not Saved', ['response' => 403]);
    }

    $status = '';

    if (isset($_POST['vfps_status'])) {
        $status = sanitize_text_field(wp_unslash($_POST['vfps_status']));
        $status = mb_substr($status, 0, VFPS_MAX_LENGTH);
    }

    update_option(VFPS_OPTION_NAME, $status, false);

    wp_safe_redirect(home_url('/'));
    exit;
});

/**
 * Output the status UI and JavaScript on the front page.
 */
add_action('wp_footer', function () {
    if (!is_front_page()) {
        return;
    }

    $status = get_option(VFPS_OPTION_NAME, '');

    if ($status === '') {
        $status = 'No status set yet';
    }

    ?>
    <section class="vfps-status-box" aria-label="Visitor status">
        <form method="post" class="vfps-status-form">
            <?php wp_nonce_field('vfps_save_status', 'vfps_nonce'); ?>
            <input type="hidden" name="vfps_action" value="save_status">

            <label for="vfps-status-input">Set a short status</label>

            <div class="vfps-status-row">
                <input
                    id="vfps-status-input"
                    name="vfps_status"
                    type="text"
                    maxlength="<?php echo esc_attr(VFPS_MAX_LENGTH); ?>"
                    value="<?php echo esc_attr($status === 'No status set yet' ? '' : $status); ?>"
                    placeholder="What is your status?"
                >

                <button type="submit">Save</button>
            </div>
        </form>

        <div class="vfps-current-status">
            <span
                class="vfps-info-icon"
                title="<?php echo esc_attr($status); ?>"
                aria-label="<?php echo esc_attr('Current status: ' . $status); ?>"
            >ℹ</span>

            <h2><?php echo esc_html($status); ?></h2>
        </div>
    </section>

    <script>
        console.log('Visitor status:', <?php echo wp_json_encode($status); ?>);
    </script>

    <style>
        .vfps-status-box {
            position: fixed;
            right: 1rem;
            bottom: 1rem;
            z-index: 9999;
            width: min(320px, calc(100vw - 2rem));
            padding: 1rem;
            border: 1px solid #d0d7de;
            border-radius: 8px;
            background: #fff;
            color: #1f2328;
            box-shadow: 0 8px 24px rgba(140, 149, 159, 0.25);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .vfps-status-form label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .vfps-status-row {
            display: flex;
            gap: 0.5rem;
        }

        .vfps-status-row input {
            min-width: 0;
            flex: 1;
        }

        .vfps-status-row button {
            cursor: pointer;
        }

        .vfps-current-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .vfps-info-icon {
            cursor: help;
            font-size: 1.1rem;
        }

        .vfps-current-status h2 {
            margin: 0;
            font-size: 1.1rem;
            line-height: 1.3;
        }
    </style>
    <?php
});
```
