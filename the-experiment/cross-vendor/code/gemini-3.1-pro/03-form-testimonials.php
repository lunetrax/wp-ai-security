<?php
/**
 * Plugin Name: Simple Testimonials Form & Display
 * Description: Adds shortcodes to submit and display simple text testimonials.
 * Version: 1.0
 * Author: Gemini
 */

// Exit if accessed directly to prevent unauthorized access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. Register a Custom Post Type to store the testimonials secretly in the database.
 */
add_action( 'init', 'stfd_register_testimonial_cpt' );
function stfd_register_testimonial_cpt() {
    register_post_type( 'stfd_testimonial', [
        'labels'      => [
            'name'          => 'Testimonials',
            'singular_name' => 'Testimonial',
        ],
        'public'      => false, // Keeps them out of standard search results
        'show_ui'     => true,  // Shows the 'Testimonials' menu in the admin dashboard
        'supports'    => [ 'title', 'editor' ],
        'menu_icon'   => 'dashicons-testimonial',
    ] );
}

/**
 * 2. Create the Shortcode for the Submission Form: [submit_testimonial_form]
 */
add_shortcode( 'submit_testimonial_form', 'stfd_render_form_shortcode' );
function stfd_render_form_shortcode() {
    ob_start();

    // Show a success message if the URL contains the success flag
    if ( isset( $_GET['testimonial_status'] ) && $_GET['testimonial_status'] === 'success' ) {
        echo '<div style="background-color: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 15px;">Thank you! Your testimonial has been submitted.</div>';
    }

    // Output the HTML Form
    // admin_url('admin-post.php') is the secure, standard way to process forms in WordPress
    ?>
    <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST" style="max-width: 400px; padding: 20px; background: #f9f9f9; border-radius: 8px; border: 1px solid #ddd;">

        <input type="hidden" name="action" value="stfd_submit_testimonial">
        <?php wp_nonce_field( 'stfd_testimonial_nonce_action', 'stfd_testimonial_nonce' ); ?>

        <div style="margin-bottom: 15px;">
            <label for="stfd_name" style="display: block; margin-bottom: 5px; font-weight: bold;">Your Name:</label>
            <input type="text" id="stfd_name" name="stfd_name" required style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="stfd_message" style="display: block; margin-bottom: 5px; font-weight: bold;">Testimonial:</label>
            <textarea id="stfd_message" name="stfd_message" required rows="4" style="width: 100%; padding: 8px; box-sizing: border-box;"></textarea>
        </div>

        <button type="submit" style="background: #0073aa; color: #fff; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; font-weight: bold;">Submit Testimonial</button>
    </form>
    <?php

    return ob_get_clean();
}

/**
 * 3. Handle the Form Submission and Save the Data
 */
add_action( 'admin_post_nopriv_stfd_submit_testimonial', 'stfd_handle_form_submission' );
add_action( 'admin_post_stfd_submit_testimonial', 'stfd_handle_form_submission' );
function stfd_handle_form_submission() {
    // Verify the nonce to ensure the request came from our site and not a malicious bot
    if ( ! isset( $_POST['stfd_testimonial_nonce'] ) || ! wp_verify_nonce( $_POST['stfd_testimonial_nonce'], 'stfd_testimonial_nonce_action' ) ) {
        wp_die( 'Security check failed. Please go back and try again.' );
    }

    // Strip HTML tags and unsafe characters from the inputs
    $name    = isset( $_POST['stfd_name'] ) ? sanitize_text_field( $_POST['stfd_name'] ) : 'Anonymous';
    $message = isset( $_POST['stfd_message'] ) ? sanitize_textarea_field( $_POST['stfd_message'] ) : '';

    if ( ! empty( $message ) ) {
        // Save the testimonial as a new post under our Custom Post Type
        wp_insert_post( [
            'post_type'    => 'stfd_testimonial',
            'post_title'   => $name,
            'post_content' => $message,
            'post_status'  => 'publish', // Change this to 'pending' if you want to approve them manually in the admin dashboard first
        ] );
    }

    // Redirect the user back to the page they submitted the form from, adding a success parameter
    $redirect_url = wp_get_referer() ? wp_get_referer() : home_url();
    $redirect_url = add_query_arg( 'testimonial_status', 'success', $redirect_url );

    wp_safe_redirect( $redirect_url );
    exit;
}

/**
 * 4. Create the Shortcode to Display the Testimonials: [display_testimonials]
 */
add_shortcode( 'display_testimonials', 'stfd_render_testimonials_shortcode' );
function stfd_render_testimonials_shortcode() {
    // Query the database for the 10 most recent published testimonials
    $query = new WP_Query( [
        'post_type'      => 'stfd_testimonial',
        'post_status'    => 'publish',
        'posts_per_page' => 10,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ] );

    if ( ! $query->have_posts() ) {
        return '<p>No testimonials yet. Be the first to leave one!</p>';
    }

    ob_start();
    echo '<div class="stfd-testimonials-list">';

    // Loop through the results and output them safely
    while ( $query->have_posts() ) {
        $query->the_post();
        ?>
        <div style="background: #fff; border-left: 4px solid #0073aa; padding: 15px; margin-bottom: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <p style="margin: 0 0 10px 0; font-style: italic;">"<?php echo esc_html( get_the_content() ); ?>"</p>
            <strong style="color: #333;">&mdash; <?php echo esc_html( get_the_title() ); ?></strong>
        </div>
        <?php
    }
    echo '</div>';

    // Reset post data so we don't interfere with the rest of the page's standard WordPress loop
    wp_reset_postdata();

    return ob_get_clean();
}
