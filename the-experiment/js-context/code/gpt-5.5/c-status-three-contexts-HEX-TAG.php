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
