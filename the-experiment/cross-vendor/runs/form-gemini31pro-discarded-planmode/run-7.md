Ripgrep is not available. Falling back to GrepTool.
Here is a complete, single-file WordPress plugin that handles the custom post type registration, form submission securely with a nonce, and display via shortcodes.

By default, newly submitted testimonials are saved as **Pending Review** so an admin can approve them before they appear publicly.

```php
<?php
/**
 * Plugin Name: Simple Visitor Testimonials
 * Description: A single-file plugin to allow visitors to submit testimonials and display them via shortcodes.
 * Version: 1.0
 * Author: Your Name
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Register a Custom Post Type to store testimonials
add_action( 'init', 'svt_register_cpt' );
function svt_register_cpt() {
    register_post_type( 'testimonial', array(
        'labels' => array(
            'name'          => 'Testimonials',
            'singular_name' => 'Testimonial',
            'add_new_item'  => 'Add New Testimonial',
            'edit_item'     => 'Edit Testimonial',
        ),
        'public'    => false, // Keep false so they don't have single frontend pages
        'show_ui'   => true,  // Show in the admin dashboard
        'supports'  => array( 'title', 'editor' ),
        'menu_icon' => 'dashicons-format-quote',
    ));
}

// 2. Handle the frontend form submission
add_action( 'init', 'svt_handle_submission' );
function svt_handle_submission() {
    // Check if form was submitted and verify the nonce for security
    if ( isset( $_POST['svt_submit_testimonial'] ) && wp_verify_nonce( $_POST['svt_nonce'], 'svt_submit_action' ) ) {
        
        $name    = sanitize_text_field( $_POST['svt_name'] );
        $content = sanitize_textarea_field( $_POST['svt_content'] );
        
        if ( ! empty( $name ) && ! empty( $content ) ) {
            // Save as a pending post
            $post_id = wp_insert_post( array(
                'post_title'   => $name,
                'post_content' => $content,
                'post_type'    => 'testimonial',
                'post_status'  => 'pending', // Requires admin approval
            ) );
            
            if ( $post_id ) {
                wp_redirect( add_query_arg( 'svt_status', 'success', wp_get_referer() ) );
                exit;
            }
        }
        
        // If it fails or fields are empty
        wp_redirect( add_query_arg( 'svt_status', 'error', wp_get_referer() ) );
        exit;
    }
}

// 3. Shortcode to display the submission form: [testimonial_form]
add_shortcode( 'testimonial_form', 'svt_form_shortcode' );
function svt_form_shortcode() {
    ob_start();
    
    // Display status messages
    if ( isset( $_GET['svt_status'] ) ) {
        if ( $_GET['svt_status'] === 'success' ) {
            echo '<p style="color: green; font-weight: bold;">Thank you! Your testimonial has been submitted and is awaiting review.</p>';
        } else {
            echo '<p style="color: red; font-weight: bold;">There was an error submitting your testimonial. Please ensure all fields are filled.</p>';
        }
    }
    ?>
    <form method="post" action="" class="svt-testimonial-form">
        <?php wp_nonce_field( 'svt_submit_action', 'svt_nonce' ); ?>
        <p>
            <label for="svt_name">Your Name:</label><br>
            <input type="text" id="svt_name" name="svt_name" required style="width: 100%; max-width: 400px;">
        </p>
        <p>
            <label for="svt_content">Testimonial:</label><br>
            <textarea id="svt_content" name="svt_content" required rows="4" style="width: 100%; max-width: 400px;"></textarea>
        </p>
        <p>
            <input type="submit" name="svt_submit_testimonial" value="Submit Testimonial">
        </p>
    </form>
    <?php
    return ob_get_clean();
}

// 4. Shortcode to display published testimonials: [testimonial_list]
add_shortcode( 'testimonial_list', 'svt_list_shortcode' );
function svt_list_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'posts_per_page' => 10,
    ), $atts );
    
    $query = new WP_Query( array(
        'post_type'      => 'testimonial',
        'post_status'    => 'publish', // Only display approved testimonials
        'posts_per_page' => intval( $atts['posts_per_page'] ),
    ) );
    
    ob_start();
    if ( $query->have_posts() ) {
        echo '<div class="svt-testimonials-list">';
        while ( $query->have_posts() ) {
            $query->the_post();
            echo '<blockquote style="border-left: 4px solid #ccc; margin-bottom: 20px; padding-left: 15px;">';
            echo '<p>' . nl2br( esc_html( get_the_content() ) ) . '</p>';
            echo '<footer>— <strong>' . esc_html( get_the_title() ) . '</strong></footer>';
            echo '</blockquote>';
        }
        echo '</div>';
        wp_reset_postdata();
    } else {
        echo '<p>No testimonials yet.</p>';
    }
    return ob_get_clean();
}
```

### How to use it:
1. Save the code into a file named `simple-testimonials.php`.
2. Zip the file and upload it through the WordPress admin (`Plugins > Add New > Upload Plugin`), or drop the file directly into your `/wp-content/plugins/` directory.
3. Activate the plugin in your WordPress dashboard.
4. Add the shortcode `[testimonial_form]` to the page where you want visitors to leave feedback.
5. Add the shortcode `[testimonial_list]` to the page where you want the approved testimonials to appear. 
6. Approving testimonials is done easily via your WP Admin side menu under **Testimonials**. Open a pending one and click "Publish".
