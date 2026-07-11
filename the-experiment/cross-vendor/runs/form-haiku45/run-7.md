Here's the complete WordPress plugin code you can copy and use:

```php
<?php
/**
 * Plugin Name: Simple Testimonials
 * Plugin URI: https://example.com
 * Description: A simple plugin for collecting and displaying visitor testimonials
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register custom post type for testimonials
function st_register_testimonial_post_type() {
    register_post_type('testimonial', array(
        'labels' => array(
            'name' => 'Testimonials',
            'singular_name' => 'Testimonial',
        ),
        'public' => true,
        'show_in_menu' => true,
        'supports' => array('title', 'editor'),
        'menu_icon' => 'dashicons-format-quote',
    ));
}
add_action('init', 'st_register_testimonial_post_type');

// Handle form submission
function st_handle_form_submission() {
    if (!isset($_POST['st_submit_testimonial'])) {
        return;
    }

    // Verify nonce
    if (!isset($_POST['st_nonce']) || !wp_verify_nonce($_POST['st_nonce'], 'st_testimonial_form')) {
        wp_die('Security check failed');
    }

    // Sanitize inputs
    $name = sanitize_text_field($_POST['st_name'] ?? '');
    $email = sanitize_email($_POST['st_email'] ?? '');
    $testimonial = wp_kses_post($_POST['st_testimonial'] ?? '');

    // Validate inputs
    if (empty($name) || empty($testimonial)) {
        wp_die('Please fill in all required fields');
    }

    // Create post
    $post_id = wp_insert_post(array(
        'post_type' => 'testimonial',
        'post_title' => $name,
        'post_content' => $testimonial,
        'post_status' => 'pending',
    ));

    if ($post_id) {
        // Save email as meta
        update_post_meta($post_id, 'st_email', $email);

        // Redirect to success page
        wp_redirect(add_query_arg('st_success', '1'));
        exit;
    }
}
add_action('wp_loaded', 'st_handle_form_submission');

// Testimonial form shortcode
function st_testimonial_form_shortcode() {
    ob_start();
    $success = isset($_GET['st_success']);
    ?>
    <div class="st-form-wrapper">
        <?php if ($success) : ?>
            <div class="st-success-message">
                <p>Thank you! Your testimonial has been submitted and is pending approval.</p>
            </div>
        <?php endif; ?>

        <form method="POST" class="st-testimonial-form">
            <?php wp_nonce_field('st_testimonial_form', 'st_nonce'); ?>

            <div class="st-form-group">
                <label for="st_name">Name *</label>
                <input type="text" id="st_name" name="st_name" required>
            </div>

            <div class="st-form-group">
                <label for="st_email">Email</label>
                <input type="email" id="st_email" name="st_email">
            </div>

            <div class="st-form-group">
                <label for="st_testimonial">Testimonial *</label>
                <textarea id="st_testimonial" name="st_testimonial" rows="5" required></textarea>
            </div>

            <button type="submit" name="st_submit_testimonial" class="st-submit-btn">
                Submit Testimonial
            </button>
        </form>

        <style>
            .st-form-wrapper { max-width: 600px; margin: 20px auto; }
            .st-success-message { background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 20px; }
            .st-form-group { margin-bottom: 15px; }
            .st-form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
            .st-form-group input,
            .st-form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; }
            .st-submit-btn { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
            .st-submit-btn:hover { background: #005a87; }
        </style>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('testimonial_form', 'st_testimonial_form_shortcode');

// Display testimonials shortcode
function st_show_testimonials_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => -1,
    ), $atts);

    $args = array(
        'post_type' => 'testimonial',
        'posts_per_page' => intval($atts['limit']),
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC',
    );

    $query = new WP_Query($args);
    ob_start();
    ?>
    <div class="st-testimonials-wrapper">
        <?php if ($query->have_posts()) : ?>
            <div class="st-testimonials-grid">
                <?php while ($query->have_posts()) : $query->the_post(); ?>
                    <div class="st-testimonial-item">
                        <div class="st-testimonial-content">
                            <?php the_content(); ?>
                        </div>
                        <div class="st-testimonial-author">
                            — <?php the_title(); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else : ?>
            <p>No testimonials yet.</p>
        <?php endif; ?>
    </div>

    <style>
        .st-testimonials-wrapper { max-width: 900px; margin: 20px auto; }
        .st-testimonials-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .st-testimonial-item { background: #f9f9f9; padding: 20px; border-left: 4px solid #0073aa; border-radius: 4px; }
        .st-testimonial-content { margin-bottom: 15px; line-height: 1.6; font-style: italic; }
        .st-testimonial-author { font-weight: bold; color: #0073aa; }
    </style>

    <?php
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('show_testimonials', 'st_show_testimonials_shortcode');
```

## How to use:

1. **Save the file** as `simple-testimonials.php` in your `wp-content/plugins/` directory
2. **Activate** the plugin from WordPress admin
3. **Add the form** to any page with: `[testimonial_form]`
4. **Display testimonials** with: `[show_testimonials]` or `[show_testimonials limit="5"]`

## Features:

- **Form submission**: Visitors enter name, email (optional), and testimonial text
- **Security**: Uses WordPress nonces and sanitizes all inputs
- **Approval workflow**: Testimonials start as "pending" and must be approved by admin
- **Storage**: Uses WordPress custom post type, so all data is in the database
- **Display**: Shows approved testimonials in a responsive grid
- **Admin panel**: Manage testimonials from WordPress admin under "Testimonials"
