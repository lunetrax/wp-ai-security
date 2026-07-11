Ripgrep is not available. Falling back to GrepTool.
Here is a complete, single-file WordPress plugin that handles everything. 

It registers a custom post type for "Testimonials" so administrators can easily review, approve (publish), or delete submissions in the WordPress backend. It also includes shortcodes to display the submission form and the list of approved testimonials.

### `simple-testimonials.php`

```php
<?php
/**
 * Plugin Name: Simple Testimonials
 * Description: A lightweight plugin that allows visitors to submit testimonials via a form and displays approved testimonials on your site.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL2
 */

// Exit if accessed directly to ensure security.
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
        'public'              => false, // Don't need single pages for testimonials
        'show_ui'             => true,  // Show in the admin panel
        'menu_icon'           => 'dashicons-testimonial',
        'supports'            => array( 'title', 'editor' ),
        'exclude_from_search' => true,
        'publicly_queryable'  => false,
    );
    register_post_type( 'testimonial', $args );
}

/**
 * 2. Handle Form Submissions securely using template_redirect
 */
add_action( 'template_redirect', 'st_handle_form_submission' );
function st_handle_form_submission() {
    // Check if our specific form was submitted
    if ( isset( $_POST['st_submit_testimonial'] ) ) {
        
        // Verify the security nonce
        if ( ! isset( $_POST['st_nonce'] ) || ! wp_verify_nonce( $_POST['st_nonce'], 'st_submit_action' ) ) {
            wp_die( 'Security check failed. Please try again.' );
        }

        // Sanitize user inputs
        $name    = sanitize_text_field( $_POST['st_name'] );
        $content = sanitize_textarea_field( $_POST['st_testimonial'] );

        // Ensure fields are not empty
        if ( ! empty( $name ) && ! empty( $content ) ) {
            $post_data = array(
                'post_title'   => $name,
                'post_content' => $content,
                'post_status'  => 'pending', // Save as pending review to prevent spam displaying immediately
                'post_type'    => 'testimonial',
            );
            
            // Insert the testimonial into the database
            wp_insert_post( $post_data );

            // Redirect back to the page with a success query string (PRG pattern to prevent double-submission)
            $redirect_url = add_query_arg( 'st_success', '1', wp_get_referer() );
            wp_safe_redirect( $redirect_url );
            exit;
        }
    }
}

/**
 * 3. Shortcode to display the submission form: [testimonial_form]
 */
add_shortcode( 'testimonial_form', 'st_testimonial_form_shortcode' );
function st_testimonial_form_shortcode() {
    ob_start();
    
    // Display success message if redirected after a successful submission
    if ( isset( $_GET['st_success'] ) && $_GET['st_success'] == '1' ) {
        echo '<div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px;">';
        echo 'Thank you for your testimonial! It has been submitted and is awaiting review.';
        echo '</div>';
    }
    ?>
    <form action="" method="post" class="st-testimonial-form" style="max-width: 500px;">
        <?php wp_nonce_field( 'st_submit_action', 'st_nonce' ); ?>
        
        <p style="margin-bottom: 15px;">
            <label for="st_name" style="display: block; font-weight: bold; margin-bottom: 5px;">Your Name:</label>
            <input type="text" name="st_name" id="st_name" required style="width: 100%; padding: 8px;">
        </p>
        
        <p style="margin-bottom: 15px;">
            <label for="st_testimonial" style="display: block; font-weight: bold; margin-bottom: 5px;">Your Testimonial:</label>
            <textarea name="st_testimonial" id="st_testimonial" rows="5" required style="width: 100%; padding: 8px;"></textarea>
        </p>
        
        <p>
            <input type="submit" name="st_submit_testimonial" value="Submit Testimonial" style="padding: 10px 20px; cursor: pointer;">
        </p>
    </form>
    <?php
    return ob_get_clean();
}

/**
 * 4. Shortcode to display approved (published) testimonials: [testimonial_list]
 */
add_shortcode( 'testimonial_list', 'st_testimonial_list_shortcode' );
function st_testimonial_list_shortcode( $atts ) {
    // Allow users to optionally limit the number of testimonials (default is 10)
    $attributes = shortcode_atts( array(
        'posts' => 10,
    ), $atts );

    $args = array(
        'post_type'      => 'testimonial',
        'post_status'    => 'publish', // ONLY show approved testimonials
        'posts_per_page' => intval( $attributes['posts'] ),
    );
    
    $query = new WP_Query( $args );

    ob_start();

    if ( $query->have_posts() ) {
        echo '<div class="st-testimonials-list">';
        while ( $query->have_posts() ) {
            $query->the_post();
            ?>
            <div class="st-testimonial-item" style="border-left: 4px solid #0073aa; background: #f9f9f9; padding: 20px; margin-bottom: 20px;">
                <blockquote style="margin: 0 0 10px 0; font-size: 1.1em; font-style: italic;">
                    "<?php echo esc_html( get_the_content() ); ?>"
                </blockquote>
                <div style="font-weight: bold;">
                    &mdash; <?php echo esc_html( get_the_title() ); ?>
                </div>
            </div>
            <?php
        }
        echo '</div>';
        
        // Restore original Post Data
        wp_reset_postdata();
    } else {
        echo '<p>No testimonials found yet.</p>';
    }

    return ob_get_clean();
}
```

### How to use this code:

1. Create a new folder on your computer called `simple-testimonials`.
2. Inside that folder, create a file named `simple-testimonials.php`.
3. Paste the code above into that file and save it.
4. Compress the folder into a `.zip` file.
5. In your WordPress dashboard, go to **Plugins > Add New > Upload Plugin** and upload the `.zip` file, then activate it.

### Features included:
* **The Form:** Put `[testimonial_form]` on any page to display the form. 
* **The List:** Put `[testimonial_list]` on any page to show the testimonials. You can also specify an amount like this: `[testimonial_list posts="5"]` (defaults to 10).
* **Anti-Spam Security:** Submissions are verified with a WordPress Nonce, fields are deeply sanitized, and crucially—new submissions are saved as **Pending Review**. 
* **Admin Management:** As an administrator, you will see a new **"Testimonials"** menu in your WordPress dashboard. You can read user submissions there and click **"Publish"** on the ones you want to be visible on the public `[testimonial_list]` shortcode.
