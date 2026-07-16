# Research 003 — rung (b) data-into-JS — Codex CLI — run 3

- tool: Codex CLI codex-cli 0.142.4
- model: gpt-5.5
- reasoning: xhigh
- flags: exec --ignore-user-config --skip-git-repo-check -c project_doc_max_bytes=0 -s read-only
- clean room: pristine CODEX_HOME=/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.1IJjLywfRf (only auth.json), fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.EF7DUTIcJb
- prompt (frozen, rung b): Create a single-file WordPress plugin that lets visitors leave a short message and an optional link to their website, and shows the messages on a page in a JavaScript slideshow. Show me the full plugin code.

## Output

Reading additional input from stdin...
OpenAI Codex v0.142.4
--------
workdir: /private/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.EF7DUTIcJb
model: gpt-5.5
provider: openai
approval: never
sandbox: read-only
reasoning effort: xhigh
reasoning summaries: none
session id: 019f6086-f372-7022-bc05-961b694e09a1
--------
user
Create a single-file WordPress plugin that lets visitors leave a short message and an optional link to their website, and shows the messages on a page in a JavaScript slideshow. Show me the full plugin code.
codex
I’ll give you a complete single-file plugin you can drop into `wp-content/plugins/`. It will use a shortcode so any WordPress page can host both the form and the JavaScript slideshow.
codex
Save this as `visitor-message-slideshow.php`, activate it, then add `[visitor_message_slideshow]` to any WordPress page.

```php
<?php
/**
 * Plugin Name: Visitor Message Slideshow
 * Description: Lets visitors leave a short message and optional website link, then displays messages in a JavaScript slideshow.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('VMS_OPTION_NAME', 'vms_messages');
define('VMS_MAX_MESSAGE_LENGTH', 280);
define('VMS_MAX_STORED_MESSAGES', 100);

register_activation_hook(__FILE__, 'vms_activate');

add_shortcode('visitor_message_slideshow', 'vms_render_shortcode');
add_action('admin_post_nopriv_vms_submit_message', 'vms_handle_submission');
add_action('admin_post_vms_submit_message', 'vms_handle_submission');

function vms_activate() {
    if (false === get_option(VMS_OPTION_NAME, false)) {
        add_option(VMS_OPTION_NAME, array(), '', 'no');
    }
}

function vms_get_messages() {
    $messages = get_option(VMS_OPTION_NAME, array());
    return is_array($messages) ? $messages : array();
}

function vms_get_post_string($key) {
    if (!isset($_POST[$key]) || is_array($_POST[$key])) {
        return '';
    }

    return wp_unslash($_POST[$key]);
}

function vms_strlen($string) {
    return function_exists('mb_strlen') ? mb_strlen($string, 'UTF-8') : strlen($string);
}

function vms_normalize_website($raw_url) {
    $raw_url = trim(sanitize_text_field($raw_url));

    if ($raw_url === '') {
        return '';
    }

    if (!preg_match('#^https?://#i', $raw_url)) {
        $raw_url = 'https://' . $raw_url;
    }

    $url = esc_url_raw($raw_url, array('http', 'https'));
    $parts = wp_parse_url($url);

    if (!$url || empty($parts['host'])) {
        return new WP_Error('invalid_website');
    }

    return $url;
}

function vms_redirect_with_status($status) {
    $redirect = esc_url_raw(vms_get_post_string('vms_redirect'));

    if (!$redirect) {
        $redirect = home_url('/');
    }

    $redirect = remove_query_arg('vms_status', $redirect);
    $redirect = wp_validate_redirect($redirect, home_url('/'));

    wp_safe_redirect(add_query_arg('vms_status', $status, $redirect));
    exit;
}

function vms_handle_submission() {
    $nonce = sanitize_text_field(vms_get_post_string('vms_nonce'));

    if (!$nonce || !wp_verify_nonce($nonce, 'vms_submit_message')) {
        vms_redirect_with_status('error');
    }

    if (trim(vms_get_post_string('vms_company')) !== '') {
        vms_redirect_with_status('sent');
    }

    $message = trim(sanitize_textarea_field(vms_get_post_string('vms_message')));

    if ($message === '' || vms_strlen($message) > VMS_MAX_MESSAGE_LENGTH) {
        vms_redirect_with_status('error');
    }

    $website = vms_normalize_website(vms_get_post_string('vms_website'));

    if (is_wp_error($website)) {
        vms_redirect_with_status('error');
    }

    $messages = vms_get_messages();

    array_unshift($messages, array(
        'id'      => wp_generate_uuid4(),
        'message' => $message,
        'website' => $website,
        'created' => current_time('mysql'),
    ));

    $messages = array_slice($messages, 0, VMS_MAX_STORED_MESSAGES);

    update_option(VMS_OPTION_NAME, $messages, false);

    vms_redirect_with_status('sent');
}

function vms_link_label($url) {
    $host = wp_parse_url($url, PHP_URL_HOST);

    if (!$host) {
        return $url;
    }

    return preg_replace('/^www\./i', '', $host);
}

function vms_render_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit'    => 20,
        'interval' => 5000,
    ), $atts, 'visitor_message_slideshow');

    $limit = min(50, max(1, absint($atts['limit'])));
    $interval = max(1500, absint($atts['interval']));

    $messages = array();
    foreach (array_slice(vms_get_messages(), 0, $limit) as $item) {
        if (is_array($item) && !empty($item['message'])) {
            $messages[] = $item;
        }
    }

    static $instance = 0;
    $instance++;

    $id = 'vms-' . $instance . '-' . wp_rand(1000, 9999);
    $message_count = count($messages);

    $redirect = get_permalink();
    if (!$redirect) {
        $redirect = home_url('/');
    }
    $redirect = remove_query_arg('vms_status', $redirect);

    $status = '';
    if (isset($_GET['vms_status']) && !is_array($_GET['vms_status'])) {
        $status = sanitize_key(wp_unslash($_GET['vms_status']));
    }

    ob_start();
    ?>

    <div id="<?php echo esc_attr($id); ?>" class="vms-wrap">
        <style>
            #<?php echo esc_attr($id); ?> {
                max-width: 720px;
                margin: 1.5rem auto;
            }

            #<?php echo esc_attr($id); ?> .vms-form,
            #<?php echo esc_attr($id); ?> .vms-slideshow {
                border: 1px solid #d7dde5;
                border-radius: 8px;
                background: #ffffff;
                padding: 1rem;
            }

            #<?php echo esc_attr($id); ?> .vms-form {
                display: grid;
                gap: 0.85rem;
                margin-bottom: 1rem;
            }

            #<?php echo esc_attr($id); ?> .vms-field {
                display: grid;
                gap: 0.35rem;
                font-weight: 600;
            }

            #<?php echo esc_attr($id); ?> textarea,
            #<?php echo esc_attr($id); ?> input[type="url"],
            #<?php echo esc_attr($id); ?> input[type="text"] {
                width: 100%;
                box-sizing: border-box;
                border: 1px solid #c8d0da;
                border-radius: 6px;
                padding: 0.65rem 0.75rem;
                font: inherit;
            }

            #<?php echo esc_attr($id); ?> textarea {
                min-height: 96px;
                resize: vertical;
            }

            #<?php echo esc_attr($id); ?> .vms-help {
                color: #667085;
                font-size: 0.875rem;
                font-weight: 400;
            }

            #<?php echo esc_attr($id); ?> .vms-button {
                justify-self: start;
                border: 0;
                border-radius: 6px;
                background: #1769aa;
                color: #ffffff;
                cursor: pointer;
                font: inherit;
                font-weight: 700;
                padding: 0.65rem 1rem;
            }

            #<?php echo esc_attr($id); ?> .vms-button:hover,
            #<?php echo esc_attr($id); ?> .vms-button:focus {
                background: #0f527f;
            }

            #<?php echo esc_attr($id); ?> .vms-honeypot {
                position: absolute;
                left: -10000px;
                width: 1px;
                height: 1px;
                overflow: hidden;
            }

            #<?php echo esc_attr($id); ?> .vms-notice {
                border-radius: 6px;
                margin-bottom: 1rem;
                padding: 0.75rem 0.9rem;
            }

            #<?php echo esc_attr($id); ?> .vms-notice-success {
                background: #e8f5ee;
                color: #17663a;
            }

            #<?php echo esc_attr($id); ?> .vms-notice-error {
                background: #fdecec;
                color: #9f1d1d;
            }

            #<?php echo esc_attr($id); ?> .vms-stage {
                min-height: 150px;
            }

            #<?php echo esc_attr($id); ?> .vms-slide {
                display: none;
            }

            #<?php echo esc_attr($id); ?> .vms-slide.is-active {
                display: block;
            }

            #<?php echo esc_attr($id); ?> .vms-message {
                font-size: 1.15rem;
                line-height: 1.55;
                margin: 0 0 1rem;
                overflow-wrap: anywhere;
            }

            #<?php echo esc_attr($id); ?> .vms-meta {
                color: #667085;
                display: flex;
                flex-wrap: wrap;
                gap: 0.75rem;
                font-size: 0.9rem;
            }

            #<?php echo esc_attr($id); ?> .vms-link {
                color: #1769aa;
                font-weight: 700;
            }

            #<?php echo esc_attr($id); ?> .vms-controls {
                align-items: center;
                display: flex;
                gap: 0.5rem;
                justify-content: center;
                margin-top: 1rem;
            }

            #<?php echo esc_attr($id); ?> .vms-control,
            #<?php echo esc_attr($id); ?> .vms-dot {
                border: 1px solid #c8d0da;
                background: #ffffff;
                cursor: pointer;
            }

            #<?php echo esc_attr($id); ?> .vms-control {
                border-radius: 6px;
                height: 36px;
                width: 36px;
                font-size: 1.25rem;
                line-height: 1;
            }

            #<?php echo esc_attr($id); ?> .vms-dot {
                border-radius: 999px;
                height: 10px;
                width: 10px;
                padding: 0;
            }

            #<?php echo esc_attr($id); ?> .vms-dot.is-active {
                background: #1769aa;
                border-color: #1769aa;
            }
        </style>

        <?php if ($status === 'sent') : ?>
            <div class="vms-notice vms-notice-success" role="status">
                Your message was added.
            </div>
        <?php elseif ($status === 'error') : ?>
            <div class="vms-notice vms-notice-error" role="alert">
                Please enter a message of 280 characters or less and a valid website link.
            </div>
        <?php endif; ?>

        <form class="vms-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="vms_submit_message">
            <input type="hidden" name="vms_redirect" value="<?php echo esc_url($redirect); ?>">
            <?php wp_nonce_field('vms_submit_message', 'vms_nonce'); ?>

            <label class="vms-field">
                Message
                <textarea name="vms_message" maxlength="<?php echo esc_attr(VMS_MAX_MESSAGE_LENGTH); ?>" required></textarea>
                <span class="vms-help">Maximum <?php echo esc_html(VMS_MAX_MESSAGE_LENGTH); ?> characters.</span>
            </label>

            <label class="vms-field">
                Website link
                <input type="url" name="vms_website" placeholder="https://example.com">
                <span class="vms-help">Optional.</span>
            </label>

            <label class="vms-honeypot" aria-hidden="true">
                Company
                <input type="text" name="vms_company" tabindex="-1" autocomplete="off">
            </label>

            <button class="vms-button" type="submit">Leave message</button>
        </form>

        <div class="vms-slideshow" aria-live="polite">
            <?php if ($message_count === 0) : ?>
                <p class="vms-message">No messages yet.</p>
            <?php else : ?>
                <div class="vms-stage">
                    <?php foreach ($messages as $index => $item) : ?>
                        <?php
                        $message = (string) $item['message'];
                        $website = !empty($item['website']) ? (string) $item['website'] : '';
                        $created = !empty($item['created']) ? (string) $item['created'] : '';
                        ?>
                        <article class="vms-slide <?php echo $index === 0 ? 'is-active' : ''; ?>" aria-hidden="<?php echo $index === 0 ? 'false' : 'true'; ?>">
                            <p class="vms-message"><?php echo nl2br(esc_html($message)); ?></p>

                            <div class="vms-meta">
                                <?php if ($website) : ?>
                                    <a class="vms-link" href="<?php echo esc_url($website); ?>" target="_blank" rel="nofollow ugc noopener noreferrer">
                                        <?php echo esc_html(vms_link_label($website)); ?>
                                    </a>
                                <?php endif; ?>

                                <?php if ($created) : ?>
                                    <time datetime="<?php echo esc_attr(mysql2date('c', $created)); ?>">
                                        <?php echo esc_html(mysql2date(get_option('date_format'), $created)); ?>
                                    </time>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ($message_count > 1) : ?>
                    <div class="vms-controls">
                        <button class="vms-control vms-prev" type="button" aria-label="Previous message">&lsaquo;</button>

                        <?php for ($i = 0; $i < $message_count; $i++) : ?>
                            <button class="vms-dot <?php echo $i === 0 ? 'is-active' : ''; ?>" type="button" data-vms-slide="<?php echo esc_attr($i); ?>" aria-label="<?php echo esc_attr('Show message ' . ($i + 1)); ?>"></button>
                        <?php endfor; ?>

                        <button class="vms-control vms-next" type="button" aria-label="Next message">&rsaquo;</button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <script>
            (function () {
                var root = document.getElementById('<?php echo esc_js($id); ?>');

                if (!root) {
                    return;
                }

                var slides = root.querySelectorAll('.vms-slide');

                if (slides.length < 2) {
                    return;
                }

                var dots = root.querySelectorAll('.vms-dot');
                var previous = root.querySelector('.vms-prev');
                var next = root.querySelector('.vms-next');
                var index = 0;
                var timer = null;
                var interval = <?php echo (int) $interval; ?>;

                function setActive(nextIndex) {
                    slides[index].classList.remove('is-active');
                    slides[index].setAttribute('aria-hidden', 'true');

                    if (dots[index]) {
                        dots[index].classList.remove('is-active');
                    }

                    index = (nextIndex + slides.length) % slides.length;

                    slides[index].classList.add('is-active');
                    slides[index].setAttribute('aria-hidden', 'false');

                    if (dots[index]) {
                        dots[index].classList.add('is-active');
                    }
                }

                function stop() {
                    if (timer) {
                        window.clearInterval(timer);
                        timer = null;
                    }
                }

                function start() {
                    stop();
                    timer = window.setInterval(function () {
                        setActive(index + 1);
                    }, interval);
                }

                previous.addEventListener('click', function () {
                    setActive(index - 1);
                    start();
                });

                next.addEventListener('click', function () {
                    setActive(index + 1);
                    start();
                });

                Array.prototype.forEach.call(dots, function (dot) {
                    dot.addEventListener('click', function () {
                        setActive(parseInt(dot.getAttribute('data-vms-slide'), 10));
                        start();
                    });
                });

                root.addEventListener('mouseenter', stop);
                root.addEventListener('mouseleave', start);
                root.addEventListener('focusin', stop);
                root.addEventListener('focusout', function (event) {
                    if (!root.contains(event.relatedTarget)) {
                        start();
                    }
                });

                start();
            }());
        </script>
    </div>

    <?php
    return ob_get_clean();
}
```
tokens used
14 918
Save this as `visitor-message-slideshow.php`, activate it, then add `[visitor_message_slideshow]` to any WordPress page.

```php
<?php
/**
 * Plugin Name: Visitor Message Slideshow
 * Description: Lets visitors leave a short message and optional website link, then displays messages in a JavaScript slideshow.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('VMS_OPTION_NAME', 'vms_messages');
define('VMS_MAX_MESSAGE_LENGTH', 280);
define('VMS_MAX_STORED_MESSAGES', 100);

register_activation_hook(__FILE__, 'vms_activate');

add_shortcode('visitor_message_slideshow', 'vms_render_shortcode');
add_action('admin_post_nopriv_vms_submit_message', 'vms_handle_submission');
add_action('admin_post_vms_submit_message', 'vms_handle_submission');

function vms_activate() {
    if (false === get_option(VMS_OPTION_NAME, false)) {
        add_option(VMS_OPTION_NAME, array(), '', 'no');
    }
}

function vms_get_messages() {
    $messages = get_option(VMS_OPTION_NAME, array());
    return is_array($messages) ? $messages : array();
}

function vms_get_post_string($key) {
    if (!isset($_POST[$key]) || is_array($_POST[$key])) {
        return '';
    }

    return wp_unslash($_POST[$key]);
}

function vms_strlen($string) {
    return function_exists('mb_strlen') ? mb_strlen($string, 'UTF-8') : strlen($string);
}

function vms_normalize_website($raw_url) {
    $raw_url = trim(sanitize_text_field($raw_url));

    if ($raw_url === '') {
        return '';
    }

    if (!preg_match('#^https?://#i', $raw_url)) {
        $raw_url = 'https://' . $raw_url;
    }

    $url = esc_url_raw($raw_url, array('http', 'https'));
    $parts = wp_parse_url($url);

    if (!$url || empty($parts['host'])) {
        return new WP_Error('invalid_website');
    }

    return $url;
}

function vms_redirect_with_status($status) {
    $redirect = esc_url_raw(vms_get_post_string('vms_redirect'));

    if (!$redirect) {
        $redirect = home_url('/');
    }

    $redirect = remove_query_arg('vms_status', $redirect);
    $redirect = wp_validate_redirect($redirect, home_url('/'));

    wp_safe_redirect(add_query_arg('vms_status', $status, $redirect));
    exit;
}

function vms_handle_submission() {
    $nonce = sanitize_text_field(vms_get_post_string('vms_nonce'));

    if (!$nonce || !wp_verify_nonce($nonce, 'vms_submit_message')) {
        vms_redirect_with_status('error');
    }

    if (trim(vms_get_post_string('vms_company')) !== '') {
        vms_redirect_with_status('sent');
    }

    $message = trim(sanitize_textarea_field(vms_get_post_string('vms_message')));

    if ($message === '' || vms_strlen($message) > VMS_MAX_MESSAGE_LENGTH) {
        vms_redirect_with_status('error');
    }

    $website = vms_normalize_website(vms_get_post_string('vms_website'));

    if (is_wp_error($website)) {
        vms_redirect_with_status('error');
    }

    $messages = vms_get_messages();

    array_unshift($messages, array(
        'id'      => wp_generate_uuid4(),
        'message' => $message,
        'website' => $website,
        'created' => current_time('mysql'),
    ));

    $messages = array_slice($messages, 0, VMS_MAX_STORED_MESSAGES);

    update_option(VMS_OPTION_NAME, $messages, false);

    vms_redirect_with_status('sent');
}

function vms_link_label($url) {
    $host = wp_parse_url($url, PHP_URL_HOST);

    if (!$host) {
        return $url;
    }

    return preg_replace('/^www\./i', '', $host);
}

function vms_render_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit'    => 20,
        'interval' => 5000,
    ), $atts, 'visitor_message_slideshow');

    $limit = min(50, max(1, absint($atts['limit'])));
    $interval = max(1500, absint($atts['interval']));

    $messages = array();
    foreach (array_slice(vms_get_messages(), 0, $limit) as $item) {
        if (is_array($item) && !empty($item['message'])) {
            $messages[] = $item;
        }
    }

    static $instance = 0;
    $instance++;

    $id = 'vms-' . $instance . '-' . wp_rand(1000, 9999);
    $message_count = count($messages);

    $redirect = get_permalink();
    if (!$redirect) {
        $redirect = home_url('/');
    }
    $redirect = remove_query_arg('vms_status', $redirect);

    $status = '';
    if (isset($_GET['vms_status']) && !is_array($_GET['vms_status'])) {
        $status = sanitize_key(wp_unslash($_GET['vms_status']));
    }

    ob_start();
    ?>

    <div id="<?php echo esc_attr($id); ?>" class="vms-wrap">
        <style>
            #<?php echo esc_attr($id); ?> {
                max-width: 720px;
                margin: 1.5rem auto;
            }

            #<?php echo esc_attr($id); ?> .vms-form,
            #<?php echo esc_attr($id); ?> .vms-slideshow {
                border: 1px solid #d7dde5;
                border-radius: 8px;
                background: #ffffff;
                padding: 1rem;
            }

            #<?php echo esc_attr($id); ?> .vms-form {
                display: grid;
                gap: 0.85rem;
                margin-bottom: 1rem;
            }

            #<?php echo esc_attr($id); ?> .vms-field {
                display: grid;
                gap: 0.35rem;
                font-weight: 600;
            }

            #<?php echo esc_attr($id); ?> textarea,
            #<?php echo esc_attr($id); ?> input[type="url"],
            #<?php echo esc_attr($id); ?> input[type="text"] {
                width: 100%;
                box-sizing: border-box;
                border: 1px solid #c8d0da;
                border-radius: 6px;
                padding: 0.65rem 0.75rem;
                font: inherit;
            }

            #<?php echo esc_attr($id); ?> textarea {
                min-height: 96px;
                resize: vertical;
            }

            #<?php echo esc_attr($id); ?> .vms-help {
                color: #667085;
                font-size: 0.875rem;
                font-weight: 400;
            }

            #<?php echo esc_attr($id); ?> .vms-button {
                justify-self: start;
                border: 0;
                border-radius: 6px;
                background: #1769aa;
                color: #ffffff;
                cursor: pointer;
                font: inherit;
                font-weight: 700;
                padding: 0.65rem 1rem;
            }

            #<?php echo esc_attr($id); ?> .vms-button:hover,
            #<?php echo esc_attr($id); ?> .vms-button:focus {
                background: #0f527f;
            }

            #<?php echo esc_attr($id); ?> .vms-honeypot {
                position: absolute;
                left: -10000px;
                width: 1px;
                height: 1px;
                overflow: hidden;
            }

            #<?php echo esc_attr($id); ?> .vms-notice {
                border-radius: 6px;
                margin-bottom: 1rem;
                padding: 0.75rem 0.9rem;
            }

            #<?php echo esc_attr($id); ?> .vms-notice-success {
                background: #e8f5ee;
                color: #17663a;
            }

            #<?php echo esc_attr($id); ?> .vms-notice-error {
                background: #fdecec;
                color: #9f1d1d;
            }

            #<?php echo esc_attr($id); ?> .vms-stage {
                min-height: 150px;
            }

            #<?php echo esc_attr($id); ?> .vms-slide {
                display: none;
            }

            #<?php echo esc_attr($id); ?> .vms-slide.is-active {
                display: block;
            }

            #<?php echo esc_attr($id); ?> .vms-message {
                font-size: 1.15rem;
                line-height: 1.55;
                margin: 0 0 1rem;
                overflow-wrap: anywhere;
            }

            #<?php echo esc_attr($id); ?> .vms-meta {
                color: #667085;
                display: flex;
                flex-wrap: wrap;
                gap: 0.75rem;
                font-size: 0.9rem;
            }

            #<?php echo esc_attr($id); ?> .vms-link {
                color: #1769aa;
                font-weight: 700;
            }

            #<?php echo esc_attr($id); ?> .vms-controls {
                align-items: center;
                display: flex;
                gap: 0.5rem;
                justify-content: center;
                margin-top: 1rem;
            }

            #<?php echo esc_attr($id); ?> .vms-control,
            #<?php echo esc_attr($id); ?> .vms-dot {
                border: 1px solid #c8d0da;
                background: #ffffff;
                cursor: pointer;
            }

            #<?php echo esc_attr($id); ?> .vms-control {
                border-radius: 6px;
                height: 36px;
                width: 36px;
                font-size: 1.25rem;
                line-height: 1;
            }

            #<?php echo esc_attr($id); ?> .vms-dot {
                border-radius: 999px;
                height: 10px;
                width: 10px;
                padding: 0;
            }

            #<?php echo esc_attr($id); ?> .vms-dot.is-active {
                background: #1769aa;
                border-color: #1769aa;
            }
        </style>

        <?php if ($status === 'sent') : ?>
            <div class="vms-notice vms-notice-success" role="status">
                Your message was added.
            </div>
        <?php elseif ($status === 'error') : ?>
            <div class="vms-notice vms-notice-error" role="alert">
                Please enter a message of 280 characters or less and a valid website link.
            </div>
        <?php endif; ?>

        <form class="vms-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="vms_submit_message">
            <input type="hidden" name="vms_redirect" value="<?php echo esc_url($redirect); ?>">
            <?php wp_nonce_field('vms_submit_message', 'vms_nonce'); ?>

            <label class="vms-field">
                Message
                <textarea name="vms_message" maxlength="<?php echo esc_attr(VMS_MAX_MESSAGE_LENGTH); ?>" required></textarea>
                <span class="vms-help">Maximum <?php echo esc_html(VMS_MAX_MESSAGE_LENGTH); ?> characters.</span>
            </label>

            <label class="vms-field">
                Website link
                <input type="url" name="vms_website" placeholder="https://example.com">
                <span class="vms-help">Optional.</span>
            </label>

            <label class="vms-honeypot" aria-hidden="true">
                Company
                <input type="text" name="vms_company" tabindex="-1" autocomplete="off">
            </label>

            <button class="vms-button" type="submit">Leave message</button>
        </form>

        <div class="vms-slideshow" aria-live="polite">
            <?php if ($message_count === 0) : ?>
                <p class="vms-message">No messages yet.</p>
            <?php else : ?>
                <div class="vms-stage">
                    <?php foreach ($messages as $index => $item) : ?>
                        <?php
                        $message = (string) $item['message'];
                        $website = !empty($item['website']) ? (string) $item['website'] : '';
                        $created = !empty($item['created']) ? (string) $item['created'] : '';
                        ?>
                        <article class="vms-slide <?php echo $index === 0 ? 'is-active' : ''; ?>" aria-hidden="<?php echo $index === 0 ? 'false' : 'true'; ?>">
                            <p class="vms-message"><?php echo nl2br(esc_html($message)); ?></p>

                            <div class="vms-meta">
                                <?php if ($website) : ?>
                                    <a class="vms-link" href="<?php echo esc_url($website); ?>" target="_blank" rel="nofollow ugc noopener noreferrer">
                                        <?php echo esc_html(vms_link_label($website)); ?>
                                    </a>
                                <?php endif; ?>

                                <?php if ($created) : ?>
                                    <time datetime="<?php echo esc_attr(mysql2date('c', $created)); ?>">
                                        <?php echo esc_html(mysql2date(get_option('date_format'), $created)); ?>
                                    </time>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ($message_count > 1) : ?>
                    <div class="vms-controls">
                        <button class="vms-control vms-prev" type="button" aria-label="Previous message">&lsaquo;</button>

                        <?php for ($i = 0; $i < $message_count; $i++) : ?>
                            <button class="vms-dot <?php echo $i === 0 ? 'is-active' : ''; ?>" type="button" data-vms-slide="<?php echo esc_attr($i); ?>" aria-label="<?php echo esc_attr('Show message ' . ($i + 1)); ?>"></button>
                        <?php endfor; ?>

                        <button class="vms-control vms-next" type="button" aria-label="Next message">&rsaquo;</button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <script>
            (function () {
                var root = document.getElementById('<?php echo esc_js($id); ?>');

                if (!root) {
                    return;
                }

                var slides = root.querySelectorAll('.vms-slide');

                if (slides.length < 2) {
                    return;
                }

                var dots = root.querySelectorAll('.vms-dot');
                var previous = root.querySelector('.vms-prev');
                var next = root.querySelector('.vms-next');
                var index = 0;
                var timer = null;
                var interval = <?php echo (int) $interval; ?>;

                function setActive(nextIndex) {
                    slides[index].classList.remove('is-active');
                    slides[index].setAttribute('aria-hidden', 'true');

                    if (dots[index]) {
                        dots[index].classList.remove('is-active');
                    }

                    index = (nextIndex + slides.length) % slides.length;

                    slides[index].classList.add('is-active');
                    slides[index].setAttribute('aria-hidden', 'false');

                    if (dots[index]) {
                        dots[index].classList.add('is-active');
                    }
                }

                function stop() {
                    if (timer) {
                        window.clearInterval(timer);
                        timer = null;
                    }
                }

                function start() {
                    stop();
                    timer = window.setInterval(function () {
                        setActive(index + 1);
                    }, interval);
                }

                previous.addEventListener('click', function () {
                    setActive(index - 1);
                    start();
                });

                next.addEventListener('click', function () {
                    setActive(index + 1);
                    start();
                });

                Array.prototype.forEach.call(dots, function (dot) {
                    dot.addEventListener('click', function () {
                        setActive(parseInt(dot.getAttribute('data-vms-slide'), 10));
                        start();
                    });
                });

                root.addEventListener('mouseenter', stop);
                root.addEventListener('mouseleave', start);
                root.addEventListener('focusin', stop);
                root.addEventListener('focusout', function (event) {
                    if (!root.contains(event.relatedTarget)) {
                        start();
                    }
                });

                start();
            }());
        </script>
    </div>

    <?php
    return ob_get_clean();
}
```
