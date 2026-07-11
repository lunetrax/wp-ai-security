Ripgrep is not available. Falling back to GrepTool.
Here is a complete, single-file WordPress plugin that handles everything. It registers a custom post type to store the testimonials, provides a form for visitors to submit them, and includes a shortcode to display them.

### Instructions:
1. Save the code below into a file named `simple-testimonials.php`.
2. Upload this file to your `/wp-content/plugins/` directory, or zip the file and upload it via the WordPress Plugins admin screen.
3. Activate the **Simple Testimonials** plugin.
4. Add the `[testimonial_form]` shortcode to any page where you want the submission form to appear.
5. Add the `[testimonial_list]` shortcode to any page where you want the approved testimonials to be displayed.

```php
<?php
/**
 * Plugin Name: Simple Testimonials
 * Plugin URI: https://example.com/
 * Description: A single-file plugin that allows visitors to submit testimonials and displays them via shortcodes.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0+
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. Register the Custom Post Type for Testimonials
 */
add_action( 'init', 'st_register_testimonial_cpt' );
function st_register_testimonial_cpt() {
    $args = array(
        'labels'              => array(
            'name'               => 'Testimonials',
            'singular_name'      => 'Testimonial',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Testimonial',
            'edit_item'          => 'Edit Testimonial',
            'new_item'           => 'New Testimonial',
            'view_item'          => 'View Testimonial',
            'search_items'       => 'Search Testimonials',
            'not_found'          => 'No testimonials found',
            'not_found_in_trash' => 'No testimonials found in Trash',
        ),
        'public'              => false, // Set to false because we don't need single view pages for these
        'show_ui'             => true,
        'publicly_queryable'  => false,
        'exclude_from_search' => true,
        'menu_icon'           => 'dashicons-testimonial',
        'supports'            => array( 'title', 'editor' ),
    );

    register_post_type( 'testimonial', $args );
}

/**
 * 2. Shortcode to display the submission form: [testimonial_form]
 */
add_shortcode( 'testimonial_form', 'st_testimonial_form_shortcode' );
function st_testimonial_form_shortcode() {
    ob_start();

    // Display success message if redirected after submission
    if ( isset( $_GET['testimonial_submitted'] ) && $_GET['testimonial_submitted'] == 'success' ) {
        echo '<div style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px;">Thank you! Your testimonial has been submitted successfully.</div>';
    }
    ?>

    <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="st-testimonial-form" style="max-width: 500px;">
        <!-- Action hook for admin-post.php -->
        <input type="hidden" name="action" value="submit_testimonial">
        
        <!-- Security Nonce -->
        <?php wp_nonce_field( 'submit_testimonial_action', 'testimonial_nonce' ); ?>

        <div style="margin-bottom: 15px;">
            <label for="t_name" style="display: block; margin-bottom: 5px;">Your Name / Company:</label>
            <input type="text" name="t_name" id="t_name" required style="width: 100%; padding: 8px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="t_content" style="display: block; margin-bottom: 5px;">Your Testimonial:</label>
            <textarea name="t_content" id="t_content" rows="5" required style="width: 100%; padding: 8px;"></textarea>
        </div>

        <div>
            <button type="submit" style="padding: 10px 20px; background-color: #0073aa; color: #fff; border: none; border-radius: 3px; cursor: pointer;">Submit Testimonial</button>
        </div>
    </form>

    <?php
    return ob_get_clean();
}

/**
 * 3. Handle the form submission
 */
add_action( 'admin_post_nopriv_submit_testimonial', 'st_handle_testimonial_submission' );
add_action( 'admin_post_submit_testimonial', 'st_handle_testimonial_submission' );

function st_handle_testimonial_submission() {
    // Verify nonce for security
    if ( ! isset( $_POST['testimonial_nonce'] ) || ! wp_verify_nonce( $_POST['testimonial_nonce'], 'submit_testimonial_action' ) ) {
        wp_die( 'Security check failed. Please try again.' );
    }

    // Sanitize user inputs
    $name    = isset( $_POST['t_name'] ) ? sanitize_text_field( $_POST['t_name'] ) : '';
    $content = isset( $_POST['t_content'] ) ? sanitize_textarea_field( $_POST['t_content'] ) : '';

    if ( ! empty( $name ) && ! empty( $content ) ) {
        // Create the post array
        $testimonial_data = array(
            'post_title'   => $name,
            'post_content' => $content,
            'post_type'    => 'testimonial',
            // Change 'publish' to 'pending' if you want to manually approve them in the WP Admin before they show up.
            'post_status'  => 'publish', 
        );

        // Insert the post into the database
        wp_insert_post( $testimonial_data );
    }

    // Redirect back to the page the user submitted the form from
    $redirect_url = wp_get_referer();
    if ( ! $redirect_url ) {
        $redirect_url = home_url();
    }
    
    $redirect_url = add_query_arg( 'testimonial_submitted', 'success', $redirect_url );
    wp_safe_redirect( $redirect_url );
    exit;
}

/**
 * 4. Shortcode to display the testimonials list: [testimonial_list]
 */
add_shortcode( 'testimonial_list', 'st_testimonial_list_shortcode' );
function st_testimonial_list_shortcode( $atts ) {
    // Allow users to pass an amount attribute, e.g., [testimonial_list count="5"]
    $atts = shortcode_atts( array(
        'count' => 10,
    ), $atts, 'testimonial_list' );

    $query_args = array(
        'post_type'      => 'testimonial',
        'post_status'    => 'publish',
        'posts_per_page' => intval( $atts['count'] ),
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    $testimonials = new WP_Query( $query_args );

    ob_start();

    if ( $testimonials->have_posts() ) {
        echo '<div class="st-testimonial-container">';
        while ( $testimonials->have_posts() ) {
            $testimonials->the_post();
            ?>
            <div class="st-single-testimonial" style="background: #f9f9f9; padding: 20px; border-left: 4px solid #0073aa; margin-bottom: 20px;">
                <blockquote style="margin: 0 0 15px 0; font-style: italic; font-size: 1.1em; border: none; padding: 0;">
                    "<?php echo wp_kses_post( get_the_content() ); ?>"
                </blockquote>
                <div class="st-testimonial-author" style="font-weight: bold; color: #333;">
                    &mdash; <?php echo esc_html( get_the_title() ); ?>
                </div>
            </div>
            <?php
        }
        echo '</div>';
        wp_reset_postdata();
    } else {
        echo '<p>No testimonials available yet.</p>';
    }

    return ob_get_clean();
}
```

### Features included:
* **Custom Post Type (CPT):** Automatically registers a "Testimonial" post type to take advantage of WordPress's native database routing and admin interface. You can view, edit, and delete them right from the WP Admin dashboard.
* **`[testimonial_form]`:** Renders an unstyled, cleanly formatted HTML form. It safely processes submissions using WordPress's `admin-post.php` handler and nonces (security tokens) to prevent CSRF attacks. 
* **Auto-publish vs Moderation:** By default, it sets the `post_status` to `'publish'` so submissions instantly appear on the page. If you want to review them first, you can simply change `'publish'` to `'pending'` on line 91.
* **`[testimonial_list]`:** Queries the database and loops through the published testimonials with some basic inline styling to present them attractively. You can also limit the number of testimonials it shows by using `[testimonial_list count="5"]`.
