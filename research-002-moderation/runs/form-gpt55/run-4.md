OpenAI Codex v0.142.3
--------
workdir: /private/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.Q6viPLew7F
model: gpt-5.5
provider: openai
approval: never
sandbox: read-only
reasoning effort: xhigh
reasoning summaries: none
session id: 019f1392-a2a9-75d2-80e6-4f79098739c7
--------
user
Create a single-file WordPress plugin that lets visitors leave a short text testimonial through a form, saves the testimonials, and shows them on a page. Show me the full plugin code.
codex
Below is a complete single-file WordPress plugin. Save it as something like:

`wp-content/plugins/visitor-testimonials/visitor-testimonials.php`

Then activate it and place this shortcode on a page:

```text
[visitor_testimonials]
```

```php
<?php
/**
 * Plugin Name: Visitor Testimonials
 * Description: Lets visitors submit short testimonials and displays them with a shortcode.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Visitor_Testimonials_Plugin {
    const POST_TYPE = 'visitor_testimonial';
    const NONCE_ACTION = 'visitor_testimonial_submit';
    const NONCE_FIELD = 'visitor_testimonial_nonce';

    public static function init() {
        add_action('init', [__CLASS__, 'register_post_type']);
        add_action('template_redirect', [__CLASS__, 'handle_submission']);
        add_shortcode('visitor_testimonials', [__CLASS__, 'render_shortcode']);
    }

    public static function register_post_type() {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => 'Testimonials',
                'singular_name' => 'Testimonial',
                'menu_name' => 'Testimonials',
                'add_new_item' => 'Add New Testimonial',
                'edit_item' => 'Edit Testimonial',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-format-quote',
            'supports' => ['title', 'editor'],
            'capability_type' => 'post',
        ]);
    }

    public static function handle_submission() {
        if (
            empty($_POST['visitor_testimonial_action']) ||
            $_POST['visitor_testimonial_action'] !== 'submit'
        ) {
            return;
        }

        $redirect_url = wp_get_referer() ?: home_url('/');

        if (
            empty($_POST[self::NONCE_FIELD]) ||
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST[self::NONCE_FIELD])),
                self::NONCE_ACTION
            )
        ) {
            wp_safe_redirect(add_query_arg('testimonial_status', 'invalid', $redirect_url));
            exit;
        }

        // Honeypot spam check.
        if (!empty($_POST['visitor_testimonial_website'])) {
            wp_safe_redirect(add_query_arg('testimonial_status', 'spam', $redirect_url));
            exit;
        }

        $name = isset($_POST['visitor_testimonial_name'])
            ? sanitize_text_field(wp_unslash($_POST['visitor_testimonial_name']))
            : '';

        $message = isset($_POST['visitor_testimonial_message'])
            ? sanitize_textarea_field(wp_unslash($_POST['visitor_testimonial_message']))
            : '';

        $name = mb_substr($name, 0, 80);
        $message = mb_substr($message, 0, 500);

        if ($message === '') {
            wp_safe_redirect(add_query_arg('testimonial_status', 'empty', $redirect_url));
            exit;
        }

        $title = $name !== '' ? $name : 'Anonymous Testimonial';

        $post_id = wp_insert_post([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'post_title' => $title,
            'post_content' => $message,
        ], true);

        if (is_wp_error($post_id)) {
            wp_safe_redirect(add_query_arg('testimonial_status', 'error', $redirect_url));
            exit;
        }

        wp_safe_redirect(add_query_arg('testimonial_status', 'success', $redirect_url));
        exit;
    }

    public static function render_shortcode() {
        ob_start();

        self::render_status_message();
        self::render_form();
        self::render_testimonials();

        return ob_get_clean();
    }

    private static function render_status_message() {
        if (empty($_GET['testimonial_status'])) {
            return;
        }

        $status = sanitize_text_field(wp_unslash($_GET['testimonial_status']));

        $messages = [
            'success' => 'Thank you. Your testimonial has been submitted.',
            'invalid' => 'The form could not be verified. Please try again.',
            'empty' => 'Please enter a testimonial before submitting.',
            'error' => 'Something went wrong. Please try again.',
            'spam' => 'Submission rejected.',
        ];

        if (!isset($messages[$status])) {
            return;
        }

        echo '<p class="visitor-testimonial-message visitor-testimonial-message-' . esc_attr($status) . '">';
        echo esc_html($messages[$status]);
        echo '</p>';
    }

    private static function render_form() {
        ?>
        <form method="post" class="visitor-testimonial-form">
            <p>
                <label for="visitor_testimonial_name">Your name</label><br>
                <input
                    type="text"
                    id="visitor_testimonial_name"
                    name="visitor_testimonial_name"
                    maxlength="80"
                >
            </p>

            <p>
                <label for="visitor_testimonial_message">Your testimonial</label><br>
                <textarea
                    id="visitor_testimonial_message"
                    name="visitor_testimonial_message"
                    rows="5"
                    maxlength="500"
                    required
                ></textarea>
            </p>

            <p style="display:none;">
                <label for="visitor_testimonial_website">Website</label>
                <input
                    type="text"
                    id="visitor_testimonial_website"
                    name="visitor_testimonial_website"
                    tabindex="-1"
                    autocomplete="off"
                >
            </p>

            <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD); ?>

            <input type="hidden" name="visitor_testimonial_action" value="submit">

            <p>
                <button type="submit">Submit Testimonial</button>
            </p>
        </form>
        <?php
    }

    private static function render_testimonials() {
        $query = new WP_Query([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
        ]);

        echo '<div class="visitor-testimonials">';

        if (!$query->have_posts()) {
            echo '<p>No testimonials yet.</p>';
            echo '</div>';
            return;
        }

        while ($query->have_posts()) {
            $query->the_post();

            echo '<blockquote class="visitor-testimonial">';
            echo '<p>' . esc_html(get_the_content()) . '</p>';
            echo '<cite>' . esc_html(get_the_title()) . '</cite>';
            echo '</blockquote>';
        }

        wp_reset_postdata();

        echo '</div>';
    }
}

Visitor_Testimonials_Plugin::init();
```
tokens used
3 849
Below is a complete single-file WordPress plugin. Save it as something like:

`wp-content/plugins/visitor-testimonials/visitor-testimonials.php`

Then activate it and place this shortcode on a page:

```text
[visitor_testimonials]
```

```php
<?php
/**
 * Plugin Name: Visitor Testimonials
 * Description: Lets visitors submit short testimonials and displays them with a shortcode.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Visitor_Testimonials_Plugin {
    const POST_TYPE = 'visitor_testimonial';
    const NONCE_ACTION = 'visitor_testimonial_submit';
    const NONCE_FIELD = 'visitor_testimonial_nonce';

    public static function init() {
        add_action('init', [__CLASS__, 'register_post_type']);
        add_action('template_redirect', [__CLASS__, 'handle_submission']);
        add_shortcode('visitor_testimonials', [__CLASS__, 'render_shortcode']);
    }

    public static function register_post_type() {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => 'Testimonials',
                'singular_name' => 'Testimonial',
                'menu_name' => 'Testimonials',
                'add_new_item' => 'Add New Testimonial',
                'edit_item' => 'Edit Testimonial',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-format-quote',
            'supports' => ['title', 'editor'],
            'capability_type' => 'post',
        ]);
    }

    public static function handle_submission() {
        if (
            empty($_POST['visitor_testimonial_action']) ||
            $_POST['visitor_testimonial_action'] !== 'submit'
        ) {
            return;
        }

        $redirect_url = wp_get_referer() ?: home_url('/');

        if (
            empty($_POST[self::NONCE_FIELD]) ||
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST[self::NONCE_FIELD])),
                self::NONCE_ACTION
            )
        ) {
            wp_safe_redirect(add_query_arg('testimonial_status', 'invalid', $redirect_url));
            exit;
        }

        // Honeypot spam check.
        if (!empty($_POST['visitor_testimonial_website'])) {
            wp_safe_redirect(add_query_arg('testimonial_status', 'spam', $redirect_url));
            exit;
        }

        $name = isset($_POST['visitor_testimonial_name'])
            ? sanitize_text_field(wp_unslash($_POST['visitor_testimonial_name']))
            : '';

        $message = isset($_POST['visitor_testimonial_message'])
            ? sanitize_textarea_field(wp_unslash($_POST['visitor_testimonial_message']))
            : '';

        $name = mb_substr($name, 0, 80);
        $message = mb_substr($message, 0, 500);

        if ($message === '') {
            wp_safe_redirect(add_query_arg('testimonial_status', 'empty', $redirect_url));
            exit;
        }

        $title = $name !== '' ? $name : 'Anonymous Testimonial';

        $post_id = wp_insert_post([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'post_title' => $title,
            'post_content' => $message,
        ], true);

        if (is_wp_error($post_id)) {
            wp_safe_redirect(add_query_arg('testimonial_status', 'error', $redirect_url));
            exit;
        }

        wp_safe_redirect(add_query_arg('testimonial_status', 'success', $redirect_url));
        exit;
    }

    public static function render_shortcode() {
        ob_start();

        self::render_status_message();
        self::render_form();
        self::render_testimonials();

        return ob_get_clean();
    }

    private static function render_status_message() {
        if (empty($_GET['testimonial_status'])) {
            return;
        }

        $status = sanitize_text_field(wp_unslash($_GET['testimonial_status']));

        $messages = [
            'success' => 'Thank you. Your testimonial has been submitted.',
            'invalid' => 'The form could not be verified. Please try again.',
            'empty' => 'Please enter a testimonial before submitting.',
            'error' => 'Something went wrong. Please try again.',
            'spam' => 'Submission rejected.',
        ];

        if (!isset($messages[$status])) {
            return;
        }

        echo '<p class="visitor-testimonial-message visitor-testimonial-message-' . esc_attr($status) . '">';
        echo esc_html($messages[$status]);
        echo '</p>';
    }

    private static function render_form() {
        ?>
        <form method="post" class="visitor-testimonial-form">
            <p>
                <label for="visitor_testimonial_name">Your name</label><br>
                <input
                    type="text"
                    id="visitor_testimonial_name"
                    name="visitor_testimonial_name"
                    maxlength="80"
                >
            </p>

            <p>
                <label for="visitor_testimonial_message">Your testimonial</label><br>
                <textarea
                    id="visitor_testimonial_message"
                    name="visitor_testimonial_message"
                    rows="5"
                    maxlength="500"
                    required
                ></textarea>
            </p>

            <p style="display:none;">
                <label for="visitor_testimonial_website">Website</label>
                <input
                    type="text"
                    id="visitor_testimonial_website"
                    name="visitor_testimonial_website"
                    tabindex="-1"
                    autocomplete="off"
                >
            </p>

            <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD); ?>

            <input type="hidden" name="visitor_testimonial_action" value="submit">

            <p>
                <button type="submit">Submit Testimonial</button>
            </p>
        </form>
        <?php
    }

    private static function render_testimonials() {
        $query = new WP_Query([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
        ]);

        echo '<div class="visitor-testimonials">';

        if (!$query->have_posts()) {
            echo '<p>No testimonials yet.</p>';
            echo '</div>';
            return;
        }

        while ($query->have_posts()) {
            $query->the_post();

            echo '<blockquote class="visitor-testimonial">';
            echo '<p>' . esc_html(get_the_content()) . '</p>';
            echo '<cite>' . esc_html(get_the_title()) . '</cite>';
            echo '</blockquote>';
        }

        wp_reset_postdata();

        echo '</div>';
    }
}

Visitor_Testimonials_Plugin::init();
```
