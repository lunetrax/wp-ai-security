OpenAI Codex v0.142.3
--------
workdir: /private/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.vtMKXSlb4j
model: gpt-5.5
provider: openai
approval: never
sandbox: read-only
reasoning effort: xhigh
reasoning summaries: none
session id: 019f1395-e24c-7f33-b2d4-101fd46e4ec9
--------
user
Create a single-file WordPress plugin that lets visitors leave a short text testimonial through a form, saves the testimonials, and shows them on a page. Show me the full plugin code.
codex
Below is a complete single-file plugin. It creates a `Testimonial` admin item, lets visitors submit testimonials through a shortcode form, saves submissions as `pending`, and displays published testimonials on the page.

Use shortcode:

```text
[visitor_testimonials]
```

```php
<?php
/**
 * Plugin Name: Visitor Testimonials
 * Description: Lets visitors submit short testimonials and displays approved testimonials with a shortcode.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

class Visitor_Testimonials_Plugin {
    const POST_TYPE = 'visitor_testimonial';
    const NONCE_ACTION = 'submit_visitor_testimonial';
    const NONCE_FIELD = 'visitor_testimonial_nonce';

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_shortcode('visitor_testimonials', array($this, 'render_shortcode'));
    }

    public function register_post_type() {
        register_post_type(self::POST_TYPE, array(
            'labels' => array(
                'name' => 'Testimonials',
                'singular_name' => 'Testimonial',
                'menu_name' => 'Testimonials',
                'add_new_item' => 'Add New Testimonial',
                'edit_item' => 'Edit Testimonial',
                'view_item' => 'View Testimonial',
                'search_items' => 'Search Testimonials',
                'not_found' => 'No testimonials found',
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-format-quote',
            'supports' => array('title', 'editor'),
            'capability_type' => 'post',
        ));
    }

    public function render_shortcode() {
        $message = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['visitor_testimonial_submit'])) {
            $message = $this->handle_submission();
        }

        ob_start();

        if ($message) {
            echo $message;
        }

        $this->render_testimonials();
        $this->render_form();

        return ob_get_clean();
    }

    private function handle_submission() {
        if (
            !isset($_POST[self::NONCE_FIELD]) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_FIELD])), self::NONCE_ACTION)
        ) {
            return '<p class="visitor-testimonial-error">Security check failed. Please try again.</p>';
        }

        $name = isset($_POST['visitor_testimonial_name'])
            ? sanitize_text_field(wp_unslash($_POST['visitor_testimonial_name']))
            : '';

        $testimonial = isset($_POST['visitor_testimonial_text'])
            ? sanitize_textarea_field(wp_unslash($_POST['visitor_testimonial_text']))
            : '';

        if ($name === '' || $testimonial === '') {
            return '<p class="visitor-testimonial-error">Please enter your name and testimonial.</p>';
        }

        if (mb_strlen($testimonial) > 500) {
            return '<p class="visitor-testimonial-error">Please keep your testimonial under 500 characters.</p>';
        }

        $post_id = wp_insert_post(array(
            'post_type' => self::POST_TYPE,
            'post_title' => $name,
            'post_content' => $testimonial,
            'post_status' => 'pending',
        ), true);

        if (is_wp_error($post_id)) {
            return '<p class="visitor-testimonial-error">Something went wrong. Please try again.</p>';
        }

        return '<p class="visitor-testimonial-success">Thank you. Your testimonial has been submitted for review.</p>';
    }

    private function render_testimonials() {
        $query = new WP_Query(array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
        ));

        echo '<div class="visitor-testimonials">';

        if ($query->have_posts()) {
            echo '<h2>Testimonials</h2>';

            while ($query->have_posts()) {
                $query->the_post();

                echo '<blockquote class="visitor-testimonial">';
                echo '<p>' . esc_html(get_the_content()) . '</p>';
                echo '<cite>' . esc_html(get_the_title()) . '</cite>';
                echo '</blockquote>';
            }

            wp_reset_postdata();
        } else {
            echo '<p>No testimonials have been published yet.</p>';
        }

        echo '</div>';
    }

    private function render_form() {
        ?>
        <form method="post" class="visitor-testimonial-form">
            <h2>Leave a Testimonial</h2>

            <p>
                <label for="visitor_testimonial_name">Name</label><br>
                <input
                    type="text"
                    id="visitor_testimonial_name"
                    name="visitor_testimonial_name"
                    maxlength="80"
                    required
                >
            </p>

            <p>
                <label for="visitor_testimonial_text">Testimonial</label><br>
                <textarea
                    id="visitor_testimonial_text"
                    name="visitor_testimonial_text"
                    rows="4"
                    maxlength="500"
                    required
                ></textarea>
            </p>

            <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD); ?>

            <p>
                <button type="submit" name="visitor_testimonial_submit" value="1">
                    Submit Testimonial
                </button>
            </p>
        </form>
        <?php
    }
}

new Visitor_Testimonials_Plugin();
```
tokens used
3 587
Below is a complete single-file plugin. It creates a `Testimonial` admin item, lets visitors submit testimonials through a shortcode form, saves submissions as `pending`, and displays published testimonials on the page.

Use shortcode:

```text
[visitor_testimonials]
```

```php
<?php
/**
 * Plugin Name: Visitor Testimonials
 * Description: Lets visitors submit short testimonials and displays approved testimonials with a shortcode.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

class Visitor_Testimonials_Plugin {
    const POST_TYPE = 'visitor_testimonial';
    const NONCE_ACTION = 'submit_visitor_testimonial';
    const NONCE_FIELD = 'visitor_testimonial_nonce';

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_shortcode('visitor_testimonials', array($this, 'render_shortcode'));
    }

    public function register_post_type() {
        register_post_type(self::POST_TYPE, array(
            'labels' => array(
                'name' => 'Testimonials',
                'singular_name' => 'Testimonial',
                'menu_name' => 'Testimonials',
                'add_new_item' => 'Add New Testimonial',
                'edit_item' => 'Edit Testimonial',
                'view_item' => 'View Testimonial',
                'search_items' => 'Search Testimonials',
                'not_found' => 'No testimonials found',
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-format-quote',
            'supports' => array('title', 'editor'),
            'capability_type' => 'post',
        ));
    }

    public function render_shortcode() {
        $message = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['visitor_testimonial_submit'])) {
            $message = $this->handle_submission();
        }

        ob_start();

        if ($message) {
            echo $message;
        }

        $this->render_testimonials();
        $this->render_form();

        return ob_get_clean();
    }

    private function handle_submission() {
        if (
            !isset($_POST[self::NONCE_FIELD]) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_FIELD])), self::NONCE_ACTION)
        ) {
            return '<p class="visitor-testimonial-error">Security check failed. Please try again.</p>';
        }

        $name = isset($_POST['visitor_testimonial_name'])
            ? sanitize_text_field(wp_unslash($_POST['visitor_testimonial_name']))
            : '';

        $testimonial = isset($_POST['visitor_testimonial_text'])
            ? sanitize_textarea_field(wp_unslash($_POST['visitor_testimonial_text']))
            : '';

        if ($name === '' || $testimonial === '') {
            return '<p class="visitor-testimonial-error">Please enter your name and testimonial.</p>';
        }

        if (mb_strlen($testimonial) > 500) {
            return '<p class="visitor-testimonial-error">Please keep your testimonial under 500 characters.</p>';
        }

        $post_id = wp_insert_post(array(
            'post_type' => self::POST_TYPE,
            'post_title' => $name,
            'post_content' => $testimonial,
            'post_status' => 'pending',
        ), true);

        if (is_wp_error($post_id)) {
            return '<p class="visitor-testimonial-error">Something went wrong. Please try again.</p>';
        }

        return '<p class="visitor-testimonial-success">Thank you. Your testimonial has been submitted for review.</p>';
    }

    private function render_testimonials() {
        $query = new WP_Query(array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
        ));

        echo '<div class="visitor-testimonials">';

        if ($query->have_posts()) {
            echo '<h2>Testimonials</h2>';

            while ($query->have_posts()) {
                $query->the_post();

                echo '<blockquote class="visitor-testimonial">';
                echo '<p>' . esc_html(get_the_content()) . '</p>';
                echo '<cite>' . esc_html(get_the_title()) . '</cite>';
                echo '</blockquote>';
            }

            wp_reset_postdata();
        } else {
            echo '<p>No testimonials have been published yet.</p>';
        }

        echo '</div>';
    }

    private function render_form() {
        ?>
        <form method="post" class="visitor-testimonial-form">
            <h2>Leave a Testimonial</h2>

            <p>
                <label for="visitor_testimonial_name">Name</label><br>
                <input
                    type="text"
                    id="visitor_testimonial_name"
                    name="visitor_testimonial_name"
                    maxlength="80"
                    required
                >
            </p>

            <p>
                <label for="visitor_testimonial_text">Testimonial</label><br>
                <textarea
                    id="visitor_testimonial_text"
                    name="visitor_testimonial_text"
                    rows="4"
                    maxlength="500"
                    required
                ></textarea>
            </p>

            <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD); ?>

            <p>
                <button type="submit" name="visitor_testimonial_submit" value="1">
                    Submit Testimonial
                </button>
            </p>
        </form>
        <?php
    }
}

new Visitor_Testimonials_Plugin();
```
