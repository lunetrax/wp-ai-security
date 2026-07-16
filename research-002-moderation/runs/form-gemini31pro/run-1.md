Ripgrep is not available. Falling back to GrepTool.
Here is a complete, single-file WordPress plugin. It registers a custom post type for testimonials, provides a shortcode to display a submission form, handles the form data securely, and includes another shortcode to display the approved testimonials.

To use this, save the code below into a file named `simple-testimonials.php`, place it in a new folder called `simple-testimonials` inside your `wp-content/plugins/` directory, and activate it from your WordPress admin dashboard.

```php
<?php
/**
 * Plugin Name: Simple Testimonials
 * Description: A single-file plugin that allows visitors to submit testimonials and displays them via shortcode.
 * Version: 1.0
 * Author: Gemini
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. Register the Custom Post Type for Testimonials
 */
function st_register_testimonial_cpt() {
    $args = array(
        'public'              => false, // Keep false so they don't have individual public pages by default
        'show_ui'             => true,  // Show in the admin menu
        'label'               => 'Testimonials',
        'menu_icon'           => 'dashicons-testimonial',
        'supports'            => array( 'title', 'editor' ),
    );
    register_post_type( 'testimonial', $args );
}
add_action( 'init', 'st_register_testimonial_cpt' );

/**
 * 2. Create the Submission Form Shortcode
 * Usage: [submit_testimonial]
 */
function st_testimonial_form_shortcode() {
    ob_start();

    // Process form submission
    if ( isset( $_POST['st_submit_testimonial'] ) ) {
        // Verify the nonce for security
        if ( wp_verify_nonce( $_POST['st_testimonial_nonce'], 'st_submit_action' ) ) {
            
            $name    = sanitize_text_field( $_POST['st_name'] );
            $content = sanitize_textarea_field( $_POST['st_content'] );

            if ( ! empty( $name ) && ! empty( $content ) ) {
                
                // Insert the testimonial as a pending post (requires admin approval to publish)
                $post_data = array(
                    'post_title'   => $name,
                    'post_content' => $content,
                    'post_type'    => 'testimonial',
                    'post_status'  => 'pending', 
                );
                
                $post_id = wp_insert_post( $post_data );
                
                if ( ! is_wp_error( $post_id ) ) {
                    echo '<p style="color: green; font-weight: bold;">Thank you! Your testimonial has been submitted and is awaiting review.</p>';
                } else {
                    echo '<p style="color: red;">There was an error saving your testimonial. Please try again.</p>';
                }
            } else {
                echo '<p style="color: red;">Please fill in both your name and your testimonial.</p>';
            }
        }
    }
    
    // Output the HTML Form
    ?>
    <form action="" method="post" class="st-testimonial-form" style="max-width: 500px; margin-bottom: 20px;">
        <?php wp_nonce_field( 'st_submit_action', 'st_testimonial_nonce' ); ?>
        <p>
            <label for="st_name">Your Name</label><br/>
            <input type="text" name="st_name" id="st_name" required style="width: 100%;">
        </p>
        <p>
            <label for="st_content">Your Testimonial</label><br/>
            <textarea name="st_content" id="st_content" rows="4" required style="width: 100%;"></textarea>
        </p>
        <p>
            <input type="submit" name="st_submit_testimonial" value="Submit Testimonial">
        </p>
    </form>
    <?php

    return ob_get_clean();
}
add_shortcode( 'submit_testimonial', 'st_testimonial_form_shortcode' );

/**
 * 3. Create the Display Shortcode
 * Usage: [display_testimonials] or [display_testimonials count="5"]
 */
function st_display_testimonials_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'count' => 10,
    ), $atts, 'display_testimonials' );

    // Query for published testimonials
    $query = new WP_Query( array(
        'post_type'      => 'testimonial',
        'post_status'    => 'publish',
        'posts_per_page' => intval( $atts['count'] ),
        'orderby'        => 'date',
        'order'          => 'DESC'
    ) );

    ob_start();

    if ( $query->have_posts() ) {
        echo '<div class="st-testimonials-list">';
        while ( $query->have_posts() ) {
            $query->the_post();
            ?>
            <div class="st-testimonial" style="border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 8px; background: #fafafa;">
                <blockquote style="margin: 0 0 15px 0; font-style: italic; font-size: 1.1em;">
                    "<?php echo esc_html( wp_strip_all_tags( get_the_content() ) ); ?>"
                </blockquote>
                <p style="margin: 0; font-weight: bold; text-align: right;">
                    &mdash; <?php echo esc_html( get_the_title() ); ?>
                </p>
            </div>
            <?php
        }
        echo '</div>';
        
        // Reset post data after a custom query
        wp_reset_postdata();
    } else {
        echo '<p>No testimonials found yet.</p>';
    }

    return ob_get_clean();
}
add_shortcode( 'display_testimonials', 'st_display_testimonials_shortcode' );
```

### How to use this plugin:

1. **The Form:** Add the `[submit_testimonial]` shortcode to any Page or Post where you want visitors to leave their feedback.
2. **Moderation:** To prevent spam from automatically appearing on your site, submitted testimonials are saved securely as a **Pending** custom post. You can view, edit, and hit **Publish** on them under the new "Testimonials" menu item in your WordPress dashboard.
3. **Displaying the Testimonials:** Add the `[display_testimonials]` shortcode to any Page where you want to show the approved feedback. You can also specify how many to show, like this: `[display_testimonials count="5"]`.
