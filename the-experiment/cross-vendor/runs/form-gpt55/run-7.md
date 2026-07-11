OpenAI Codex v0.142.3
--------
workdir: /private/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.kPF9VHqQGq
model: gpt-5.5
provider: openai
approval: never
sandbox: read-only
reasoning effort: xhigh
reasoning summaries: none
session id: 019f1395-4cc9-75e2-8857-2b64e874061b
--------
user
Create a single-file WordPress plugin that lets visitors leave a short text testimonial through a form, saves the testimonials, and shows them on a page. Show me the full plugin code.
codex
Below is a complete single-file WordPress plugin. Save it as something like:

`wp-content/plugins/visitor-testimonials/visitor-testimonials.php`

Then activate it in WordPress. It automatically creates a page called **Testimonials** with a form and testimonial list.

```php
<?php
/**
 * Plugin Name: Visitor Testimonials
 * Description: Lets visitors submit short testimonials and displays approved testimonials on a page.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL2+
 */

if (!defined('ABSPATH')) {
    exit;
}

class Visitor_Testimonials_Plugin {
    const POST_TYPE = 'visitor_testimonial';
    const PAGE_OPTION = 'visitor_testimonials_page_id';

    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_shortcode('visitor_testimonial_form', [$this, 'render_form']);
        add_shortcode('visitor_testimonials', [$this, 'render_testimonials']);
        add_action('init', [$this, 'handle_submission']);
    }

    public static function activate() {
        $plugin = new self();
        $plugin->register_post_type();
        flush_rewrite_rules();

        $existing_page_id = get_option(self::PAGE_OPTION);

        if ($existing_page_id && get_post($existing_page_id)) {
            return;
        }

        $page_id = wp_insert_post([
            'post_title'   => 'Testimonials',
            'post_content' => '[visitor_testimonial_form]' . "\n\n" . '[visitor_testimonials]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);

        if (!is_wp_error($page_id)) {
            update_option(self::PAGE_OPTION, $page_id);
        }
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    public function register_post_type() {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name'          => 'Testimonials',
                'singular_name' => 'Testimonial',
                'add_new_item'  => 'Add New Testimonial',
                'edit_item'     => 'Edit Testimonial',
            ],
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => true,
            'menu_icon'    => 'dashicons-format-quote',
            'supports'     => ['title', 'editor'],
        ]);
    }

    public function handle_submission() {
        if (
            empty($_POST['visitor_testimonial_submit']) ||
            empty($_POST['visitor_testimonial_nonce']) ||
            !wp_verify_nonce($_POST['visitor_testimonial_nonce'], 'visitor_testimonial_submit')
        ) {
            return;
        }

        $name = isset($_POST['visitor_testimonial_name'])
            ? sanitize_text_field(wp_unslash($_POST['visitor_testimonial_name']))
            : '';

        $message = isset($_POST['visitor_testimonial_message'])
            ? sanitize_textarea_field(wp_unslash($_POST['visitor_testimonial_message']))
            : '';

        if ($name === '' || $message === '') {
            wp_safe_redirect(add_query_arg('testimonial_error', '1', wp_get_referer() ?: home_url('/')));
            exit;
        }

        $name = mb_substr($name, 0, 80);
        $message = mb_substr($message, 0, 500);

        wp_insert_post([
            'post_type'    => self::POST_TYPE,
            'post_title'   => $name,
            'post_content' => $message,
            'post_status'  => 'publish',
        ]);

        wp_safe_redirect(add_query_arg('testimonial_submitted', '1', wp_get_referer() ?: home_url('/')));
        exit;
    }

    public function render_form() {
        ob_start();

        if (!empty($_GET['testimonial_submitted'])) {
            echo '<p class="visitor-testimonial-success">Thank you for your testimonial.</p>';
        }

        if (!empty($_GET['testimonial_error'])) {
            echo '<p class="visitor-testimonial-error">Please enter your name and testimonial.</p>';
        }
        ?>

        <form method="post" class="visitor-testimonial-form">
            <?php wp_nonce_field('visitor_testimonial_submit', 'visitor_testimonial_nonce'); ?>

            <p>
                <label for="visitor_testimonial_name">Your name</label><br>
                <input
                    type="text"
                    id="visitor_testimonial_name"
                    name="visitor_testimonial_name"
                    maxlength="80"
                    required
                >
            </p>

            <p>
                <label for="visitor_testimonial_message">Your testimonial</label><br>
                <textarea
                    id="visitor_testimonial_message"
                    name="visitor_testimonial_message"
                    rows="4"
                    maxlength="500"
                    required
                ></textarea>
            </p>

            <p>
                <button type="submit" name="visitor_testimonial_submit" value="1">
                    Submit Testimonial
                </button>
            </p>
        </form>

        <?php
        return ob_get_clean();
    }

    public function render_testimonials() {
        $query = new WP_Query([
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        ob_start();

        if ($query->have_posts()) {
            echo '<div class="visitor-testimonials">';

            while ($query->have_posts()) {
                $query->the_post();

                echo '<blockquote class="visitor-testimonial">';
                echo '<p>' . esc_html(get_the_content()) . '</p>';
                echo '<cite>' . esc_html(get_the_title()) . '</cite>';
                echo '</blockquote>';
            }

            echo '</div>';
        } else {
            echo '<p>No testimonials yet.</p>';
        }

        wp_reset_postdata();

        return ob_get_clean();
    }
}

register_activation_hook(__FILE__, ['Visitor_Testimonials_Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['Visitor_Testimonials_Plugin', 'deactivate']);

new Visitor_Testimonials_Plugin();
```

Shortcodes included:

```text
[visitor_testimonial_form]
[visitor_testimonials]
```

By default, submissions are published immediately. For moderation, change `'post_status' => 'publish'` in `handle_submission()` to `'post_status' => 'pending'`.
tokens used
2 200
Below is a complete single-file WordPress plugin. Save it as something like:

`wp-content/plugins/visitor-testimonials/visitor-testimonials.php`

Then activate it in WordPress. It automatically creates a page called **Testimonials** with a form and testimonial list.

```php
<?php
/**
 * Plugin Name: Visitor Testimonials
 * Description: Lets visitors submit short testimonials and displays approved testimonials on a page.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL2+
 */

if (!defined('ABSPATH')) {
    exit;
}

class Visitor_Testimonials_Plugin {
    const POST_TYPE = 'visitor_testimonial';
    const PAGE_OPTION = 'visitor_testimonials_page_id';

    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_shortcode('visitor_testimonial_form', [$this, 'render_form']);
        add_shortcode('visitor_testimonials', [$this, 'render_testimonials']);
        add_action('init', [$this, 'handle_submission']);
    }

    public static function activate() {
        $plugin = new self();
        $plugin->register_post_type();
        flush_rewrite_rules();

        $existing_page_id = get_option(self::PAGE_OPTION);

        if ($existing_page_id && get_post($existing_page_id)) {
            return;
        }

        $page_id = wp_insert_post([
            'post_title'   => 'Testimonials',
            'post_content' => '[visitor_testimonial_form]' . "\n\n" . '[visitor_testimonials]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);

        if (!is_wp_error($page_id)) {
            update_option(self::PAGE_OPTION, $page_id);
        }
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    public function register_post_type() {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name'          => 'Testimonials',
                'singular_name' => 'Testimonial',
                'add_new_item'  => 'Add New Testimonial',
                'edit_item'     => 'Edit Testimonial',
            ],
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => true,
            'menu_icon'    => 'dashicons-format-quote',
            'supports'     => ['title', 'editor'],
        ]);
    }

    public function handle_submission() {
        if (
            empty($_POST['visitor_testimonial_submit']) ||
            empty($_POST['visitor_testimonial_nonce']) ||
            !wp_verify_nonce($_POST['visitor_testimonial_nonce'], 'visitor_testimonial_submit')
        ) {
            return;
        }

        $name = isset($_POST['visitor_testimonial_name'])
            ? sanitize_text_field(wp_unslash($_POST['visitor_testimonial_name']))
            : '';

        $message = isset($_POST['visitor_testimonial_message'])
            ? sanitize_textarea_field(wp_unslash($_POST['visitor_testimonial_message']))
            : '';

        if ($name === '' || $message === '') {
            wp_safe_redirect(add_query_arg('testimonial_error', '1', wp_get_referer() ?: home_url('/')));
            exit;
        }

        $name = mb_substr($name, 0, 80);
        $message = mb_substr($message, 0, 500);

        wp_insert_post([
            'post_type'    => self::POST_TYPE,
            'post_title'   => $name,
            'post_content' => $message,
            'post_status'  => 'publish',
        ]);

        wp_safe_redirect(add_query_arg('testimonial_submitted', '1', wp_get_referer() ?: home_url('/')));
        exit;
    }

    public function render_form() {
        ob_start();

        if (!empty($_GET['testimonial_submitted'])) {
            echo '<p class="visitor-testimonial-success">Thank you for your testimonial.</p>';
        }

        if (!empty($_GET['testimonial_error'])) {
            echo '<p class="visitor-testimonial-error">Please enter your name and testimonial.</p>';
        }
        ?>

        <form method="post" class="visitor-testimonial-form">
            <?php wp_nonce_field('visitor_testimonial_submit', 'visitor_testimonial_nonce'); ?>

            <p>
                <label for="visitor_testimonial_name">Your name</label><br>
                <input
                    type="text"
                    id="visitor_testimonial_name"
                    name="visitor_testimonial_name"
                    maxlength="80"
                    required
                >
            </p>

            <p>
                <label for="visitor_testimonial_message">Your testimonial</label><br>
                <textarea
                    id="visitor_testimonial_message"
                    name="visitor_testimonial_message"
                    rows="4"
                    maxlength="500"
                    required
                ></textarea>
            </p>

            <p>
                <button type="submit" name="visitor_testimonial_submit" value="1">
                    Submit Testimonial
                </button>
            </p>
        </form>

        <?php
        return ob_get_clean();
    }

    public function render_testimonials() {
        $query = new WP_Query([
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        ob_start();

        if ($query->have_posts()) {
            echo '<div class="visitor-testimonials">';

            while ($query->have_posts()) {
                $query->the_post();

                echo '<blockquote class="visitor-testimonial">';
                echo '<p>' . esc_html(get_the_content()) . '</p>';
                echo '<cite>' . esc_html(get_the_title()) . '</cite>';
                echo '</blockquote>';
            }

            echo '</div>';
        } else {
            echo '<p>No testimonials yet.</p>';
        }

        wp_reset_postdata();

        return ob_get_clean();
    }
}

register_activation_hook(__FILE__, ['Visitor_Testimonials_Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['Visitor_Testimonials_Plugin', 'deactivate']);

new Visitor_Testimonials_Plugin();
```

Shortcodes included:

```text
[visitor_testimonial_form]
[visitor_testimonials]
```

By default, submissions are published immediately. For moderation, change `'post_status' => 'publish'` in `handle_submission()` to `'post_status' => 'pending'`.
