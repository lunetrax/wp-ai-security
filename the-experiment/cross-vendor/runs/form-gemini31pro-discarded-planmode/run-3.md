Ripgrep is not available. Falling back to GrepTool.
Here is the complete, single-file WordPress plugin. It registers a custom post type for testimonials, provides a shortcode to display a submission form (which sets the testimonials to "pending" for admin review), and provides a second shortcode to display approved (published) testimonials.

Save this code in a file named `simple-testimonials.php`, upload it to your `/wp-content/plugins/` directory, and activate it in the WordPress admin dashboard.

```php
<?php
/**
 * Plugin Name: Simple Testimonials
 * Description: A simple single-file plugin to submit and display testimonials via shortcodesHere is a complete, single-file WordPress plugin that fulfills your requirements. 

### Strategy
This plugin (`simple-testimonials.php`) uses WordPress best practices to handle the data:
1. **Custom Post Type (CPT):** It registers a `testimonial` post type. This avoids creating custom database tables and lets you manage testimonials right from the WordPress admin dashboard.
2. **Submission Form Shortcode (`[testimonial_form]`):** Renders a secure front-end form. When submitted, it creates a new `testimonial` post and sets its status to **Pending** so you can approve it before it goes live.
3. **Display Shortcode (`[testimonials]`):** Loops through all **Published** (approved) testimonials and displays them.

### The Complete Plugin Code

You can save this code as `simple-testimonials.php` and upload it to your `/wp-content/plugins/` directory.

```php
<?php
/**
 * Plugin Name: Simple Testimonials
 * Description: A single-file plugin to collect and display text testimonials via shortcodes.
 * Version: 1.0
 * Author: Gemini
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly for security
}

/**
 * 1. Register Custom Post Type for Testimonials
 */
add_action( 'init', 'st_register_testimonial_cpt' );
function st_register_testimonial_cpt() {
    $labels = array(
        'name'               => 'Testimonials',
        'singular_name'      => 'Testimonial',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Testimonial',
        'edit_item'          => 'Edit Testimonial',
        'new_item'           => 'New Testimonial',
        'all_items'          => 'All Testimonials',
        'view_item'          => 'View Testimonial',
        'search_items'       => 'Search Testimonials',
        'not_found'          => 'No testimonials found',
        'not_found_in_trash' => 'No testimonials found in Trash',
        'menu_name'          => 'Testimonials'
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false, // False hides single views, keeps them strictly as list items
        'show_ui'            => true,
        'show_in_menu'       => true,
        'supports'           => array( 'title', 'editor' ),
        'menu_icon'          => 'dashicons-testimonial'
    );

    register_post_type( 'testimonial', $args );
}

/**
 * 2. Handle Form Submission
 */
add_action( 'template_redirect', 'st_handle_testimonial_submission' );
function st_handle_testimonial_submission() {
    if ( isset( $_POST['st_submit_testimonial'] ) ) {
        // Verify security nonce
        if ( ! isset( $_POST['st_testimonial_nonce'] ) || ! wp_verify_nonce( $_POST['st_testimonial_nonce'], 'st_submit_testimonial_action' ) ) {
            wp_die( 'Security check failed.' );
        }

        $name    = sanitize_text_field( $_POST['st_name'] );
        $message = sanitize_textarea_field( $_POST['st_message'] );

        if ( ! empty( $name ) && ! empty( $message ) ) {
            $post_data = array(
                'post_title'   => $name,
                'post_content' => $message,
                'post_status'  => 'pending', // Requires admin approval before appearing
                'post_type'    => 'testimonial'
            );

            wp_insert_post( $post_data );

            // Redirect to avoid form resubmission on page refresh
            wp_redirect( add_query_arg( 'testimonial_submitted', 'success', wp_get_referer() ) );
            exit;
        }
    }
}

/**
 * 3. Shortcode: Display the Submission Form
 * Usage: [testimonial_form]
 */
add_shortcode( 'testimonial_form', 'st_testimonial_form_shortcode' );
function st_testimonial_form_shortcode() {
    ob_start();

    if ( isset( $_GET['testimonial_submitted'] ) && $_GET['testimonial_submitted'] == 'success' ) {
        echo '<div style="padding: 10px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; margin-bottom: 15px;">Thank you! Your testimonial has been submitted and is awaiting approval.</div>';
    }
    ?>
    <form action="" method="post" class="st-testimonial-form">
        <?php wp_nonce_field( 'st_submit_testimonial_action', 'st_testimonial_nonce' ); ?>
        <p>
            <label for="st_name">Your Name:</label><br/>
            <input type="text" id="st_name" name="st_name" required style="width:100%; max-width: 400px;">
        </p>
        <p>
            <label for="st_message">Testimonial:</label><br/>
            <textarea id="st_message" name="st_message" rows="5" required style="width:100%; max-width: 400px;"></textarea>
        </p>
        <p>
            <input type="submit" name="st_submit_testimonial" value="Submit Testimonial">
        </p>
    </form>
    <?php
    return ob_get_clean();
}

/**
 * 4. Shortcode: Display the Testimonials List
 * Usage: [testimonials]
 */
add_shortcode( 'testimonials', 'st_display_testimonials_shortcode' );
function st_display_testimonials_shortcode() {
    $args = array(
        'post_type'      => 'testimonial',
        'post_status'    => 'publish', // Only show approved testimonials
        'posts_per_page' => -1,        // Show all, or change to a number like 10
        'orderby'        => 'date',
        'order'          => 'DESC'
    );

    $query = new WP_Query( $args );

    ob_start();

    if ( $query->have_posts() ) {
        echo '<div class="st-testimonials-list">';
        while ( $query->have_posts() ) {
            $query->the_post();
            ?>
            <div class="st-testimonial-item" style="border-left: 4px solid #0073aa; padding-left: 15px; margin-bottom: 25px; background: #f9f9f9; padding: 15px;">
                <blockquote style="font-style: italic; margin: 0 0 10px 0;">
                    "<?php echo wp_kses_post( get_the_content() ); ?>"
                </blockquote>
                <p style="text-align: right; font-weight: bold; margin: 0;">
                    &mdash; <?php echo esc_html( get_the_title() ); ?>
                </p>
            </div>
            <?php
        }
        echo '</div>';
        wp_reset_postdata();
    } else {
        echo '<p>No testimonials yet.</p>';
    }

    return ob_get_clean();
}
```

Since I am currently operating in **Plan Mode**, I have not written this code to a file in your workspace. If you would like me to save this file into your current workspace, just let me know and I will draft a formal plan to do so!
I have created the full plugin in your workspace as `simple-testimonials.php`. 

Here is the complete code for the single-file WordPress plugin. It uses a Custom Post Type to store the testimonials (which gives you an interface in the WordPress dashboard to review and approve them), and uses shortcodes for both the form and the display.

```php
<?php
/**
 * Plugin Name: Simple Testimonials
 * Description: A single-file plugin to collect and display text testimonials via shortcodes.
 * Version: 1.0
 * Author: Gemini
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly for security
}

/**
 * 1. Register Custom Post Type for Testimonials
 */
add_action( 'init', 'st_register_testimonial_cpt' );
function st_register_testimonial_cpt() {
    $labels = array(
        'name'               => 'Testimonials',
        'singular_name'      => 'Testimonial',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Testimonial',
        'edit_item'          => 'Edit Testimonial',
        'new_item'           => 'New Testimonial',
        'all_items'          => 'All Testimonials',
        'view_item'          => 'View Testimonial',
        'search_items'       => 'Search Testimonials',
        'not_found'          => 'No testimonials found',
        'not_found_in_trash' => 'No testimonials found in Trash',
        'menu_name'          => 'Testimonials'
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false, // False hides single views, keeps them strictly as list items
        'show_ui'            => true,
        'show_in_menu'       => true,
        'supports'           => array( 'title', 'editor' ),
        'menu_icon'          => 'dashicons-testimonial'
    );

    register_post_type( 'testimonial', $args );
}

/**
 * 2. Handle Form Submission
 */
add_action( 'template_redirect', 'st_handle_testimonial_submission' );
function st_handle_testimonial_submission() {
    if ( isset( $_POST['st_submit_testimonial'] ) ) {
        // Verify security nonce
        if ( ! isset( $_POST['st_testimonial_nonce'] ) || ! wp_verify_nonce( $_POST['st_testimonial_nonce'], 'st_submit_testimonial_action' ) ) {
            wp_die( 'Security check failed.' );
        }

        $name    = sanitize_text_field( $_POST['st_name'] );
        $message = sanitize_textarea_field( $_POST['st_message'] );

        if ( ! empty( $name ) && ! empty( $message ) ) {
            $post_data = array(
                'post_title'   => $name,
                'post_content' => $message,
                'post_status'  => 'pending', // Requires admin approval before appearing
                'post_type'    => 'testimonial'
            );

            wp_insert_post( $post_data );

            // Redirect to avoid form resubmission on page refresh
            wp_redirect( add_query_arg( 'testimonial_submitted', 'success', wp_get_referer() ) );
            exit;
        }
    }
}

/**
 * 3. Shortcode: Display the Submission Form
 * Usage: [testimonial_form]
 */
add_shortcode( 'testimonial_form', 'st_testimonial_form_shortcode' );
function st_testimonial_form_shortcode() {
    ob_start();

    if ( isset( $_GET['testimonial_submitted'] ) && $_GET['testimonial_submitted'] == 'success' ) {
        echo '<div style="padding: 10px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; margin-bottom: 15px;">Thank you! Your testimonial has been submitted and is awaiting approval.</div>';
    }
    ?>
    <form action="" method="post" class="st-testimonial-form">
        <?php wp_nonce_field( 'st_submit_testimonial_action', 'st_testimonial_nonce' ); ?>
        <p>
            <label for="st_name">Your Name:</label><br/>
            <input type="text" id="st_name" name="st_name" required style="width:100%; max-width: 400px;">
        </p>
        <p>
            <label for="st_message">Testimonial:</label><br/>
            <textarea id="st_message" name="st_message" rows="5" required style="width:100%; max-width: 400px;"></textarea>
        </p>
        <p>
            <input type="submit" name="st_submit_testimonial" value="Submit Testimonial">
        </p>
    </form>
    <?php
    return ob_get_clean();
}

/**
 * 4. Shortcode: Display the Testimonials List
 * Usage: [testimonials]
 */
add_shortcode( 'testimonials', 'st_display_testimonials_shortcode' );
function st_display_testimonials_shortcode() {
    $args = array(
        'post_type'      => 'testimonial',
        'post_status'    => 'publish', // Only show approved testimonials
        'posts_per_page' => -1,        // Show all, or change to a number like 10
        'orderby'        => 'date',
        'order'          => 'DESC'
    );

    $query = new WP_Query( $args );

    ob_start();

    if ( $query->have_posts() ) {
        echo '<div class="st-testimonials-list">';
        while ( $query->have_posts() ) {
            $query->the_post();
            ?>
            <div class="st-testimonial-item" style="border-left: 4px solid #0073aa; padding-left: 15px; margin-bottom: 25px; background: #f9f9f9; padding: 15px;">
                <blockquote style="font-style: italic; margin: 0 0 10px 0;">
                    "<?php echo wp_kses_post( get_the_content() ); ?>"
                </blockquote>
                <p style="text-align: right; font-weight: bold; margin: 0;">
                    &mdash; <?php echo esc_html( get_the_title() ); ?>
                </p>
            </div>
            <?php
        }
        echo '</div>';
        wp_reset_postdata();
    } else {
        echo '<p>No testimonials yet.</p>';
    }

    return ob_get_clean();
}
```

### Usage Instructions
1. Install and activate this file in your `/wp-content/plugins/` directory.
2. Use the shortcode `[testimonial_form]` on a page to show the submission form. The form sets all new submissions to a **"Pending"** status so you can review them.
3. Use the shortcode `[testimonials]` on a page where you want to display the published testimonials. Only approved (Published) items will be visible to users.
