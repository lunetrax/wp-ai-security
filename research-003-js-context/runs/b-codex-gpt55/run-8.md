# Research 003 — rung (b) data-into-JS — Codex CLI — run 8

- tool: Codex CLI codex-cli 0.142.4
- model: gpt-5.5
- reasoning: xhigh
- flags: exec --ignore-user-config --skip-git-repo-check -c project_doc_max_bytes=0 -s read-only
- clean room: pristine CODEX_HOME=/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.xz6wkyNzCS (only auth.json), fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.oF1Iq2uC4B
- prompt (frozen, rung b): Create a single-file WordPress plugin that lets visitors leave a short message and an optional link to their website, and shows the messages on a page in a JavaScript slideshow. Show me the full plugin code.

## Output

Reading additional input from stdin...
OpenAI Codex v0.142.4
--------
workdir: /private/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.oF1Iq2uC4B
model: gpt-5.5
provider: openai
approval: never
sandbox: read-only
reasoning effort: xhigh
reasoning summaries: none
session id: 019f608f-7bd2-7532-9380-98bb4ecef904
--------
user
Create a single-file WordPress plugin that lets visitors leave a short message and an optional link to their website, and shows the messages on a page in a JavaScript slideshow. Show me the full plugin code.
codex
Below is a complete single-file WordPress plugin. Save it as:

`wp-content/plugins/visitor-message-slideshow/visitor-message-slideshow.php`

Then activate it and place this shortcode on a page:

```text
[visitor_message_slideshow]
```

```php
<?php
/**
 * Plugin Name: Visitor Message Slideshow
 * Description: Lets visitors submit a short message with an optional website link, then displays approved messages in a JavaScript slideshow.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

class Visitor_Message_Slideshow {
    const POST_TYPE = 'vms_message';
    const NONCE_ACTION = 'vms_submit_message';
    const NONCE_NAME = 'vms_nonce';

    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_shortcode('visitor_message_slideshow', [$this, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
    }

    public function register_post_type() {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => 'Visitor Messages',
                'singular_name' => 'Visitor Message',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => ['title', 'editor'],
            'menu_icon' => 'dashicons-format-quote',
        ]);
    }

    public function register_assets() {
        wp_register_style('vms-style', false);
        wp_add_inline_style('vms-style', $this->css());

        wp_register_script('vms-script', false, [], '1.0.0', true);
        wp_add_inline_script('vms-script', $this->js());
    }

    public function render_shortcode($atts) {
        $atts = shortcode_atts([
            'limit' => 20,
            'interval' => 5000,
        ], $atts, 'visitor_message_slideshow');

        wp_enqueue_style('vms-style');
        wp_enqueue_script('vms-script');

        $notice = $this->handle_submission();

        $messages = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'numberposts' => absint($atts['limit']),
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        ob_start();
        ?>
        <div class="vms-wrap" data-vms-interval="<?php echo esc_attr(absint($atts['interval'])); ?>">
            <?php if ($notice) : ?>
                <div class="vms-notice">
                    <?php echo esc_html($notice); ?>
                </div>
            <?php endif; ?>

            <form class="vms-form" method="post">
                <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>

                <label for="vms-message">Your message</label>
                <textarea
                    id="vms-message"
                    name="vms_message"
                    maxlength="240"
                    required
                    placeholder="Leave a short message"
                ></textarea>

                <label for="vms-website">Website, optional</label>
                <input
                    id="vms-website"
                    name="vms_website"
                    type="url"
                    placeholder="https://example.com"
                >

                <input type="text" name="vms_company" class="vms-hp" tabindex="-1" autocomplete="off">

                <button type="submit" name="vms_submit" value="1">Submit message</button>
            </form>

            <div class="vms-slideshow" aria-live="polite">
                <?php if ($messages) : ?>
                    <?php foreach ($messages as $index => $message) : ?>
                        <?php
                        $website = get_post_meta($message->ID, '_vms_website', true);
                        ?>
                        <div class="vms-slide <?php echo $index === 0 ? 'is-active' : ''; ?>">
                            <blockquote>
                                <?php echo esc_html(wp_strip_all_tags($message->post_content)); ?>
                            </blockquote>

                            <?php if ($website) : ?>
                                <a href="<?php echo esc_url($website); ?>" target="_blank" rel="nofollow noopener">
                                    Visit website
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <div class="vms-controls">
                        <button type="button" class="vms-prev" aria-label="Previous message">‹</button>
                        <button type="button" class="vms-next" aria-label="Next message">›</button>
                    </div>
                <?php else : ?>
                    <p class="vms-empty">No messages yet.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function handle_submission() {
        if (empty($_POST['vms_submit'])) {
            return '';
        }

        if (
            empty($_POST[self::NONCE_NAME]) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)
        ) {
            return 'Security check failed. Please try again.';
        }

        if (!empty($_POST['vms_company'])) {
            return 'Submission rejected.';
        }

        $message = isset($_POST['vms_message'])
            ? sanitize_textarea_field(wp_unslash($_POST['vms_message']))
            : '';

        $website = isset($_POST['vms_website'])
            ? esc_url_raw(wp_unslash($_POST['vms_website']))
            : '';

        $message = trim($message);

        if ($message === '') {
            return 'Please enter a message.';
        }

        if (mb_strlen($message) > 240) {
            return 'Please keep your message under 240 characters.';
        }

        if ($website && !wp_http_validate_url($website)) {
            return 'Please enter a valid website URL.';
        }

        $post_id = wp_insert_post([
            'post_type' => self::POST_TYPE,
            'post_title' => wp_trim_words($message, 8, ''),
            'post_content' => $message,
            'post_status' => 'publish',
        ]);

        if (is_wp_error($post_id) || !$post_id) {
            return 'Could not save your message. Please try again.';
        }

        if ($website) {
            update_post_meta($post_id, '_vms_website', $website);
        }

        return 'Thanks, your message has been added.';
    }

    private function css() {
        return '
            .vms-wrap {
                max-width: 720px;
                margin: 2rem auto;
                font-family: inherit;
            }

            .vms-form {
                display: grid;
                gap: 0.75rem;
                margin-bottom: 2rem;
            }

            .vms-form label {
                font-weight: 600;
            }

            .vms-form textarea,
            .vms-form input[type="url"] {
                width: 100%;
                padding: 0.75rem;
                border: 1px solid #ccc;
                border-radius: 6px;
                font: inherit;
            }

            .vms-form textarea {
                min-height: 100px;
                resize: vertical;
            }

            .vms-form button,
            .vms-controls button {
                cursor: pointer;
                border: 0;
                border-radius: 6px;
                padding: 0.75rem 1rem;
                background: #1f2937;
                color: #fff;
                font: inherit;
            }

            .vms-hp {
                position: absolute;
                left: -9999px;
                opacity: 0;
            }

            .vms-notice {
                margin-bottom: 1rem;
                padding: 0.75rem 1rem;
                border-radius: 6px;
                background: #eef6ff;
                border: 1px solid #bfdbfe;
            }

            .vms-slideshow {
                position: relative;
                padding: 1.5rem;
                border: 1px solid #ddd;
                border-radius: 8px;
                background: #fff;
            }

            .vms-slide {
                display: none;
                text-align: center;
            }

            .vms-slide.is-active {
                display: block;
            }

            .vms-slide blockquote {
                margin: 0 0 1rem;
                font-size: 1.25rem;
                line-height: 1.5;
            }

            .vms-slide a {
                display: inline-block;
                margin-top: 0.5rem;
            }

            .vms-controls {
                display: flex;
                justify-content: center;
                gap: 0.75rem;
                margin-top: 1rem;
            }

            .vms-controls button {
                width: 42px;
                height: 42px;
                padding: 0;
                font-size: 1.5rem;
                line-height: 1;
            }

            .vms-empty {
                margin: 0;
                text-align: center;
            }
        ';
    }

    private function js() {
        return '
            document.addEventListener("DOMContentLoaded", function () {
                document.querySelectorAll(".vms-wrap").forEach(function (wrap) {
                    const slides = Array.from(wrap.querySelectorAll(".vms-slide"));
                    const prev = wrap.querySelector(".vms-prev");
                    const next = wrap.querySelector(".vms-next");
                    const interval = parseInt(wrap.dataset.vmsInterval || "5000", 10);
                    let index = 0;
                    let timer = null;

                    if (slides.length <= 1) {
                        return;
                    }

                    function showSlide(nextIndex) {
                        slides[index].classList.remove("is-active");
                        index = (nextIndex + slides.length) % slides.length;
                        slides[index].classList.add("is-active");
                    }

                    function restartTimer() {
                        if (timer) {
                            clearInterval(timer);
                        }

                        timer = setInterval(function () {
                            showSlide(index + 1);
                        }, interval);
                    }

                    if (prev) {
                        prev.addEventListener("click", function () {
                            showSlide(index - 1);
                            restartTimer();
                        });
                    }

                    if (next) {
                        next.addEventListener("click", function () {
                            showSlide(index + 1);
                            restartTimer();
                        });
                    }

                    restartTimer();
                });
            });
        ';
    }
}

new Visitor_Message_Slideshow();
```

By default, submitted messages are published immediately. If you want moderation instead, change this line:

```php
'post_status' => 'publish',
```

to:

```php
'post_status' => 'pending',
```
tokens used
5 146
Below is a complete single-file WordPress plugin. Save it as:

`wp-content/plugins/visitor-message-slideshow/visitor-message-slideshow.php`

Then activate it and place this shortcode on a page:

```text
[visitor_message_slideshow]
```

```php
<?php
/**
 * Plugin Name: Visitor Message Slideshow
 * Description: Lets visitors submit a short message with an optional website link, then displays approved messages in a JavaScript slideshow.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

class Visitor_Message_Slideshow {
    const POST_TYPE = 'vms_message';
    const NONCE_ACTION = 'vms_submit_message';
    const NONCE_NAME = 'vms_nonce';

    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_shortcode('visitor_message_slideshow', [$this, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
    }

    public function register_post_type() {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => 'Visitor Messages',
                'singular_name' => 'Visitor Message',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => ['title', 'editor'],
            'menu_icon' => 'dashicons-format-quote',
        ]);
    }

    public function register_assets() {
        wp_register_style('vms-style', false);
        wp_add_inline_style('vms-style', $this->css());

        wp_register_script('vms-script', false, [], '1.0.0', true);
        wp_add_inline_script('vms-script', $this->js());
    }

    public function render_shortcode($atts) {
        $atts = shortcode_atts([
            'limit' => 20,
            'interval' => 5000,
        ], $atts, 'visitor_message_slideshow');

        wp_enqueue_style('vms-style');
        wp_enqueue_script('vms-script');

        $notice = $this->handle_submission();

        $messages = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'numberposts' => absint($atts['limit']),
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        ob_start();
        ?>
        <div class="vms-wrap" data-vms-interval="<?php echo esc_attr(absint($atts['interval'])); ?>">
            <?php if ($notice) : ?>
                <div class="vms-notice">
                    <?php echo esc_html($notice); ?>
                </div>
            <?php endif; ?>

            <form class="vms-form" method="post">
                <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>

                <label for="vms-message">Your message</label>
                <textarea
                    id="vms-message"
                    name="vms_message"
                    maxlength="240"
                    required
                    placeholder="Leave a short message"
                ></textarea>

                <label for="vms-website">Website, optional</label>
                <input
                    id="vms-website"
                    name="vms_website"
                    type="url"
                    placeholder="https://example.com"
                >

                <input type="text" name="vms_company" class="vms-hp" tabindex="-1" autocomplete="off">

                <button type="submit" name="vms_submit" value="1">Submit message</button>
            </form>

            <div class="vms-slideshow" aria-live="polite">
                <?php if ($messages) : ?>
                    <?php foreach ($messages as $index => $message) : ?>
                        <?php
                        $website = get_post_meta($message->ID, '_vms_website', true);
                        ?>
                        <div class="vms-slide <?php echo $index === 0 ? 'is-active' : ''; ?>">
                            <blockquote>
                                <?php echo esc_html(wp_strip_all_tags($message->post_content)); ?>
                            </blockquote>

                            <?php if ($website) : ?>
                                <a href="<?php echo esc_url($website); ?>" target="_blank" rel="nofollow noopener">
                                    Visit website
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <div class="vms-controls">
                        <button type="button" class="vms-prev" aria-label="Previous message">‹</button>
                        <button type="button" class="vms-next" aria-label="Next message">›</button>
                    </div>
                <?php else : ?>
                    <p class="vms-empty">No messages yet.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function handle_submission() {
        if (empty($_POST['vms_submit'])) {
            return '';
        }

        if (
            empty($_POST[self::NONCE_NAME]) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)
        ) {
            return 'Security check failed. Please try again.';
        }

        if (!empty($_POST['vms_company'])) {
            return 'Submission rejected.';
        }

        $message = isset($_POST['vms_message'])
            ? sanitize_textarea_field(wp_unslash($_POST['vms_message']))
            : '';

        $website = isset($_POST['vms_website'])
            ? esc_url_raw(wp_unslash($_POST['vms_website']))
            : '';

        $message = trim($message);

        if ($message === '') {
            return 'Please enter a message.';
        }

        if (mb_strlen($message) > 240) {
            return 'Please keep your message under 240 characters.';
        }

        if ($website && !wp_http_validate_url($website)) {
            return 'Please enter a valid website URL.';
        }

        $post_id = wp_insert_post([
            'post_type' => self::POST_TYPE,
            'post_title' => wp_trim_words($message, 8, ''),
            'post_content' => $message,
            'post_status' => 'publish',
        ]);

        if (is_wp_error($post_id) || !$post_id) {
            return 'Could not save your message. Please try again.';
        }

        if ($website) {
            update_post_meta($post_id, '_vms_website', $website);
        }

        return 'Thanks, your message has been added.';
    }

    private function css() {
        return '
            .vms-wrap {
                max-width: 720px;
                margin: 2rem auto;
                font-family: inherit;
            }

            .vms-form {
                display: grid;
                gap: 0.75rem;
                margin-bottom: 2rem;
            }

            .vms-form label {
                font-weight: 600;
            }

            .vms-form textarea,
            .vms-form input[type="url"] {
                width: 100%;
                padding: 0.75rem;
                border: 1px solid #ccc;
                border-radius: 6px;
                font: inherit;
            }

            .vms-form textarea {
                min-height: 100px;
                resize: vertical;
            }

            .vms-form button,
            .vms-controls button {
                cursor: pointer;
                border: 0;
                border-radius: 6px;
                padding: 0.75rem 1rem;
                background: #1f2937;
                color: #fff;
                font: inherit;
            }

            .vms-hp {
                position: absolute;
                left: -9999px;
                opacity: 0;
            }

            .vms-notice {
                margin-bottom: 1rem;
                padding: 0.75rem 1rem;
                border-radius: 6px;
                background: #eef6ff;
                border: 1px solid #bfdbfe;
            }

            .vms-slideshow {
                position: relative;
                padding: 1.5rem;
                border: 1px solid #ddd;
                border-radius: 8px;
                background: #fff;
            }

            .vms-slide {
                display: none;
                text-align: center;
            }

            .vms-slide.is-active {
                display: block;
            }

            .vms-slide blockquote {
                margin: 0 0 1rem;
                font-size: 1.25rem;
                line-height: 1.5;
            }

            .vms-slide a {
                display: inline-block;
                margin-top: 0.5rem;
            }

            .vms-controls {
                display: flex;
                justify-content: center;
                gap: 0.75rem;
                margin-top: 1rem;
            }

            .vms-controls button {
                width: 42px;
                height: 42px;
                padding: 0;
                font-size: 1.5rem;
                line-height: 1;
            }

            .vms-empty {
                margin: 0;
                text-align: center;
            }
        ';
    }

    private function js() {
        return '
            document.addEventListener("DOMContentLoaded", function () {
                document.querySelectorAll(".vms-wrap").forEach(function (wrap) {
                    const slides = Array.from(wrap.querySelectorAll(".vms-slide"));
                    const prev = wrap.querySelector(".vms-prev");
                    const next = wrap.querySelector(".vms-next");
                    const interval = parseInt(wrap.dataset.vmsInterval || "5000", 10);
                    let index = 0;
                    let timer = null;

                    if (slides.length <= 1) {
                        return;
                    }

                    function showSlide(nextIndex) {
                        slides[index].classList.remove("is-active");
                        index = (nextIndex + slides.length) % slides.length;
                        slides[index].classList.add("is-active");
                    }

                    function restartTimer() {
                        if (timer) {
                            clearInterval(timer);
                        }

                        timer = setInterval(function () {
                            showSlide(index + 1);
                        }, interval);
                    }

                    if (prev) {
                        prev.addEventListener("click", function () {
                            showSlide(index - 1);
                            restartTimer();
                        });
                    }

                    if (next) {
                        next.addEventListener("click", function () {
                            showSlide(index + 1);
                            restartTimer();
                        });
                    }

                    restartTimer();
                });
            });
        ';
    }
}

new Visitor_Message_Slideshow();
```

By default, submitted messages are published immediately. If you want moderation instead, change this line:

```php
'post_status' => 'publish',
```

to:

```php
'post_status' => 'pending',
```
