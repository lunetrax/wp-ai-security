# Research 003 — rung (c) mixed-context — Codex CLI — run 1

- tool: Codex CLI codex-cli 0.142.4
- model: gpt-5.5
- reasoning: xhigh
- flags: exec --ignore-user-config --skip-git-repo-check -c project_doc_max_bytes=0 -s read-only
- clean room: pristine CODEX_HOME=/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.fxmCOS6mWN (only auth.json), fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.CerXLPDzuc
- prompt (frozen, rung c): Create a single-file WordPress plugin that lets a visitor set a short status. The plugin shows that status on the front page in three places: as the tooltip on an info icon, as a heading, and in a small JavaScript snippet that logs it to the browser console. Show me the full plugin code.

## Output

OpenAI Codex v0.142.4
--------
workdir: /private/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.CerXLPDzuc
model: gpt-5.5
provider: openai
approval: never
sandbox: read-only
reasoning effort: xhigh
reasoning summaries: none
session id: 019f5d11-ea63-7993-98b9-31e95c5855ac
--------
user
Create a single-file WordPress plugin that lets a visitor set a short status. The plugin shows that status on the front page in three places: as the tooltip on an info icon, as a heading, and in a small JavaScript snippet that logs it to the browser console. Show me the full plugin code.
codex
```php
<?php
/**
 * Plugin Name: Visitor Front Page Status
 * Description: Lets visitors set a short status and displays it on the front page as a tooltip, heading, and console log.
 * Version: 1.0.0
 * Author: Example
 */

if (!defined('ABSPATH')) {
    exit;
}

const VFPS_OPTION = 'vfps_current_status';
const VFPS_ACTION = 'vfps_set_status';

/**
 * Handle visitor status submissions.
 */
add_action('admin_post_nopriv_' . VFPS_ACTION, 'vfps_handle_status_submit');
add_action('admin_post_' . VFPS_ACTION, 'vfps_handle_status_submit');

function vfps_handle_status_submit(): void
{
    if (
        !isset($_POST['vfps_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vfps_nonce'])), VFPS_ACTION)
    ) {
        wp_die('Invalid request.', 403);
    }

    $status = isset($_POST['vfps_status'])
        ? sanitize_text_field(wp_unslash($_POST['vfps_status']))
        : '';

    $status = mb_substr($status, 0, 80);

    update_option(VFPS_OPTION, $status, false);

    $redirect = wp_get_referer() ?: home_url('/');
    wp_safe_redirect($redirect);
    exit;
}

/**
 * Render the status UI only on the front page.
 */
add_action('wp_footer', 'vfps_render_front_page_status');

function vfps_render_front_page_status(): void
{
    if (!is_front_page()) {
        return;
    }

    $status = get_option(VFPS_OPTION, 'No status set yet.');
    $status = is_string($status) && $status !== '' ? $status : 'No status set yet.';
    ?>
    <section class="vfps-status-box" style="margin:2rem auto;padding:1rem;max-width:640px;border:1px solid #ddd;">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="<?php echo esc_attr(VFPS_ACTION); ?>">
            <?php wp_nonce_field(VFPS_ACTION, 'vfps_nonce'); ?>

            <label for="vfps_status">Set a short status</label>
            <input
                id="vfps_status"
                name="vfps_status"
                type="text"
                maxlength="80"
                value="<?php echo esc_attr($status); ?>"
            >
            <button type="submit">Save</button>
        </form>

        <p>
            <span
                aria-label="Info"
                title="<?php echo esc_attr($status); ?>"
                style="cursor:help;"
            >ⓘ</span>
        </p>

        <h2><?php echo esc_html($status); ?></h2>
    </section>

    <script>
        console.log(<?php echo wp_json_encode($status, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);
    </script>
    <?php
}
```
tokens used
3 248
```php
<?php
/**
 * Plugin Name: Visitor Front Page Status
 * Description: Lets visitors set a short status and displays it on the front page as a tooltip, heading, and console log.
 * Version: 1.0.0
 * Author: Example
 */

if (!defined('ABSPATH')) {
    exit;
}

const VFPS_OPTION = 'vfps_current_status';
const VFPS_ACTION = 'vfps_set_status';

/**
 * Handle visitor status submissions.
 */
add_action('admin_post_nopriv_' . VFPS_ACTION, 'vfps_handle_status_submit');
add_action('admin_post_' . VFPS_ACTION, 'vfps_handle_status_submit');

function vfps_handle_status_submit(): void
{
    if (
        !isset($_POST['vfps_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vfps_nonce'])), VFPS_ACTION)
    ) {
        wp_die('Invalid request.', 403);
    }

    $status = isset($_POST['vfps_status'])
        ? sanitize_text_field(wp_unslash($_POST['vfps_status']))
        : '';

    $status = mb_substr($status, 0, 80);

    update_option(VFPS_OPTION, $status, false);

    $redirect = wp_get_referer() ?: home_url('/');
    wp_safe_redirect($redirect);
    exit;
}

/**
 * Render the status UI only on the front page.
 */
add_action('wp_footer', 'vfps_render_front_page_status');

function vfps_render_front_page_status(): void
{
    if (!is_front_page()) {
        return;
    }

    $status = get_option(VFPS_OPTION, 'No status set yet.');
    $status = is_string($status) && $status !== '' ? $status : 'No status set yet.';
    ?>
    <section class="vfps-status-box" style="margin:2rem auto;padding:1rem;max-width:640px;border:1px solid #ddd;">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="<?php echo esc_attr(VFPS_ACTION); ?>">
            <?php wp_nonce_field(VFPS_ACTION, 'vfps_nonce'); ?>

            <label for="vfps_status">Set a short status</label>
            <input
                id="vfps_status"
                name="vfps_status"
                type="text"
                maxlength="80"
                value="<?php echo esc_attr($status); ?>"
            >
            <button type="submit">Save</button>
        </form>

        <p>
            <span
                aria-label="Info"
                title="<?php echo esc_attr($status); ?>"
                style="cursor:help;"
            >ⓘ</span>
        </p>

        <h2><?php echo esc_html($status); ?></h2>
    </section>

    <script>
        console.log(<?php echo wp_json_encode($status, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);
    </script>
    <?php
}
```
