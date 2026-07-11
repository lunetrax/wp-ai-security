Ripgrep is not available. Falling back to GrepTool.
Here is the complete, single-file WordPress plugin. It registers a custom post type to store the testimonials (which makes them easy to manage in the WordPress admin area), creates a form shortcode for visitors to submit them, and another shortcode to display them.

### Plugin Code

Save this code into a file named `simple-testimonials.php`, compress it into a `.zip` file, and upload it to your WordPress site via **Plugins > Add New > Upload Plugin**.

```php
<?php
/**
 * Plugin Name: Simple Testimonials
 * Plugin URI:  https://github.com/gemini
 * Description: A lightweight plugin that allows visitors to submit text testimonials via a form and displays them on a page using shortcodes.
 * Version:     1.0.0
 * Author:      Gemini
 * License:     GPL-2.0+
 */

// Exit if accessed directly to prevent direct file execution
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the Custom Post Type for Testimonials
 */
function st_register_testimonial_post_type() {
    $args = array(
        'labels'      => array(
            'name'          => 'Testimonials',
            'singular_name' => 'Testimonial',
            'add_new_item'  => 'Add New Testimonial',
            'edit_item'     => 'Edit Testimonial',
            'all_items'     => 'All Testimonials'
        ),
        'public'      => false, // Keep it false so it doesn't have a single page archive that themes might mess up
        'show_ui'     => true,  // Show in the admin dashboard
        'menu_icon'   => 'dashicons-testimonial',
        'supports'    => array( 'title', 'editor' ),
    );
    register_post_type( 'testimonial', $args );
}
add_action( 'init', 'st_register_testimonial_post_type' );

/**
 * Shortcode to display the submission form
 * Usage: [testimonial_form]
 */
function st_testimonial_form_shortcode() {
    ob_start();
    
    // Handle form submission
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['st_submit_testimonial'] ) ) {
        // Verify nonce for security
        if ( isset( $_POST['st_nonce'] ) && wp_verify_nonce( $_POST['st_nonce'], 'st_submit_action' ) ) {
            
            $name    = sanitize_text_field( $_POST['st_name'] );
            $message = sanitize_textarea_field( $_POST['st_message'] );
            
            if ( ! empty( $name ) && ! empty( $message ) ) {
                // Insert the testimonial as a new post
                $post_id = wp_insert_post( array(
                    'post_title'   => $name,
                    'post_content' => $message,
                    'post_type'    => 'testimonial',
                    'post_status'  => 'publish', // Change to 'pending' if you want to moderate them before they appear
                ) );
                
                if ( $post_id ) {
                    echo '<div style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px;">Thank you! Your testimonial has been submitted.</div>';
                } else {
                    echo '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px;">There was an error saving your testimonial. Please try again.</div>';
                }
            } else {
                echo '<div style="background-color: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin-bottom: 20px;">Please fill in all required fields.</div>';
            }
        }
    }
    
    // Output the form
    ?>
    <div class="st-form-container" style="max-width: 500px; margin-bottom: 30px;">
        <form method="post" action="">
            <?php wp_nonce_field( 'st_submit_action', 'st_nonce' ); ?>
            <div style="margin-bottom: 15px;">
                <label for="st_name" style="display: block; font-weight: bold; margin-bottom: 5px;">Your Name <span style="color:red;">*</span></label>
                <input type="text" id="st_name" name="st_name" required style="width: 100%; padding: 8px;" placeholder="John Doe" />
            </div>
            <div style="margin-bottom: 15px;">
                <label for="st_message" style="display: block; font-weight: bold; margin-bottom: 5px;">Your Testimonial <span style="color:red;">*</span></label>
                <textarea id="st_message" name="st_message" required rows="4" style="width: 100%; padding: 8px;" placeholder="This service is amazing..."></textarea>
            </div>
            <div>
                <input type="submit" name="st_submit_testimonial" value="Submit Testimonial" style="padding: 10px 20px; cursor: pointer; background: #0073aa; color: #fff; border: none; border-radius: 3px;" />
            </div>
        </form>
    </div>
    <?php
    
    return ob_get_clean();
}
add_shortcode( 'testimonial_form', 'st_testimonial_form_shortcode' );

/**
 * Shortcode to display the list of testimonials
 * Usage: [testimonial_list]
 */
function st_testimonial_list_shortcode() {
    // Query published testimonials
    $query_args = array(
        'post_type'      => 'testimonial',
        'post_status'    => 'publish',
        'posts_per_page' => -1, // Display all; change to a number like 10 to limit
        'orderby'        => 'date',
        'order'          => 'DESC'
    );
    
    $testimonials = new WP_Query( $query_args );
    
    ob_start();
    
    if ( $testimonials->have_posts() ) {
        echo '<div class="st-testimonials-wrapper">';
        while ( $testimonials->have_posts() ) {
            $testimonials->the_post();
            ?>
            <div class="st-testimonial-item" style="border-left: 4px solid #0073aa; background: #f9f9f9; padding: 20px; margin-bottom: 20px; border-radius: 0 4px 4px 0;">
                <div class="st-content" style="font-style: italic; margin-bottom: 15px; font-size: 1.1em;">
                    "<?php echo wp_kses_post( get_the_content() ); ?>"
                </div>
                <div class="st-author" style="font-weight: bold;">
                    &mdash; <?php echo esc_html( get_the_title() ); ?>
                </div>
            </div>
            <?php
        }
        echo '</div>';
        wp_reset_postdata();
    } else {
        echo '<p>No testimonials have been submitted yet. Be the first!</p>';
    }
    
    return ob_get_clean();
}
add_shortcode( 'testimonial_list', 'st_testimonial_list_shortcode' );
```

### How to use it:

1. **Create the Form Page:** Create a new page in WordPress (e.g., "Leave a Review") and type `[testimonial_form]` in the content. This will output the secure form where users can submit their names and messages.
2. **Create the Display Page:** Create another page (e.g., "What Our Customers Say") and type `[testimonial_list]` in the content. This will list all submitted testimonials styled in a clean blockquote format. 
3. **Manage Testimonials:** Because this uses WordPress Custom Post Types, you'll see a new "Testimonials" menu item pop up on your left-hand WordPress admin dashboard. You can edit, delete, or manually create new testimonials from there. 

*Note: The plugin is set to publish testimonials immediately upon submission. If you want to moderate them before they show up on the site, change `'post_status'  => 'publish'` to `'post_status'  => 'pending'` on line 58.*
