# Research 003 — rung (b) data-into-JS — Gemini CLI — run 7

- tool: Gemini CLI 0.49.0
- model: gemini-3.1-pro-preview (pinned to defeat Auto routing)
- flags: --skip-trust -o text
- clean room: fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.87TRUQMCgL, no user GEMINI.md/extensions/MCP
- auth: paid AI Studio API key (GEMINI_API_KEY)
- prompt (frozen, rung b): Create a single-file WordPress plugin that lets visitors leave a short message and an optional link to their website, and shows the messages on a page in a JavaScript slideshow. Show me the full plugin code.

## Output

Ripgrep is not available. Falling back to GrepTool.
[STARTUP] Phase 'cleanup_ops' was started but never ended. Skipping metrics.
[STARTUP] Cannot measure phase 'cleanup_ops': start mark 'startup:cleanup_ops:start' not found (likely cleared by reset).
Here is the complete, single-file WordPress plugin. It uses WordPress's native Custom Post Types (CPT) to store the messages, giving you a clean admin interface to edit, delete, or manage them without needing to create custom database tables. 

It provides two shortcodes:
1. `[message_form]` - Displays the form for visitors to submit their message.
2. `[message_slideshow]` - Displays the JavaScript slideshow of submitted messages.

### Instructions:
1. Create a file named `message-slideshow.php`.
2. Paste the code below into that file.
3. Upload it to your `/wp-content/plugins/` directory and activate the **Message Slideshow** plugin from your WordPress admin dashboard.
4. Add `[message_form]` and `[message_slideshow]` to any page or post.

### The Plugin Code (`message-slideshow.php`)

```php
<?php
/**
 * Plugin Name: Message Slideshow
 * Plugin URI: https://example.com
 * Description: Let visitors leave a short message and optional website link, displayed in a JavaScript slideshow.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0+
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. Register Custom Post Type for Messages
 */
add_action( 'init', 'sms_register_cpt' );
function sms_register_cpt() {
    $labels = array(
        'name'               => 'Slideshow Messages',
        'singular_name'      => 'Message',
        'add_new'            => 'Add New Message',
        'add_new_item'       => 'Add New Message',
        'edit_item'          => 'Edit Message',
        'new_item'           => 'New Message',
        'view_item'          => 'View Message',
        'search_items'       => 'Search Messages',
        'not_found'          => 'No messages found',
        'not_found_in_trash' => 'No messages found in Trash',
        'menu_name'          => 'Messages'
    );

    $args = array(
        'labels'              => $labels,
        'public'              => false, // Not accessible on the frontend via single URLs
        'show_ui'             => true,  // Show in admin menu
        'menu_position'       => 25,
        'menu_icon'           => 'dashicons-testimonial',
        'supports'            => array( 'title', 'editor' ), // Title = Name, Editor = Message
        'capability_type'     => 'post',
    );

    register_post_type( 'sms_message', $args );
}

/**
 * 2. Add Meta Box for the Optional Website Link in the Admin
 */
add_action( 'add_meta_boxes', 'sms_add_meta_box' );
function sms_add_meta_box() {
    add_meta_box(
        'sms_link_meta',
        'Website Link',
        'sms_link_meta_callback',
        'sms_message',
        'normal',
        'default'
    );
}

function sms_link_meta_callback( $post ) {
    // Add a nonce field so we can check for it later
    wp_nonce_field( 'sms_save_meta_box_data', 'sms_meta_box_nonce' );

    $value = get_post_meta( $post->ID, '_sms_website_link', true );
    echo '<label for="sms_website_link">Visitor\'s URL: </label>';
    echo '<input type="url" id="sms_website_link" name="sms_website_link" value="' . esc_attr( $value ) . '" style="width:100%; max-width: 400px;" />';
}

add_action( 'save_post', 'sms_save_meta_box_data' );
function sms_save_meta_box_data( $post_id ) {
    // Check if our nonce is set and verify it
    if ( ! isset( $_POST['sms_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['sms_meta_box_nonce'], 'sms_save_meta_box_data' ) ) {
        return;
    }
    // Ignore auto saves
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    // Check user permissions
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    // Sanitize and save user input
    if ( isset( $_POST['sms_website_link'] ) ) {
        $link = esc_url_raw( $_POST['sms_website_link'] );
        update_post_meta( $post_id, '_sms_website_link', $link );
    }
}

/**
 * 3. Frontend Form Shortcode: [message_form]
 */
add_shortcode( 'message_form', 'sms_form_shortcode' );
function sms_form_shortcode() {
    ob_start();

    // Handle form submission
    if ( isset( $_POST['sms_submit_message'] ) && isset( $_POST['sms_nonce'] ) ) {
        if ( wp_verify_nonce( $_POST['sms_nonce'], 'sms_submit' ) ) {
            $name    = sanitize_text_field( $_POST['sms_name'] );
            $message = sanitize_textarea_field( $_POST['sms_message_text'] );
            $link    = esc_url_raw( $_POST['sms_link'] );

            if ( ! empty( $name ) && ! empty( $message ) ) {
                $post_id = wp_insert_post( array(
                    'post_title'   => $name,
                    'post_content' => $message,
                    'post_type'    => 'sms_message',
                    // Auto-publishing so it appears instantly. 
                    // Change to 'pending' if you want admin approval first.
                    'post_status'  => 'publish' 
                ) );

                if ( $post_id ) {
                    update_post_meta( $post_id, '_sms_website_link', $link );
                    echo '<div style="color: green; margin-bottom: 20px; font-weight: bold;">Thank you! Your message has been posted.</div>';
                }
            } else {
                echo '<div style="color: red; margin-bottom: 20px;">Name and Message are required.</div>';
            }
        }
    }

    // Output Form
    ?>
    <form method="post" action="" class="sms-visitor-form" style="max-width: 500px; margin: 20px 0;">
        <?php wp_nonce_field( 'sms_submit', 'sms_nonce' ); ?>
        
        <div style="margin-bottom: 15px;">
            <label for="sms_name" style="display:block; margin-bottom: 5px;">Name: *</label>
            <input type="text" id="sms_name" name="sms_name" required style="width: 100%; padding: 8px;" />
        </div>
        
        <div style="margin-bottom: 15px;">
            <label for="sms_link" style="display:block; margin-bottom: 5px;">Website Link (Optional):</label>
            <input type="url" id="sms_link" name="sms_link" placeholder="https://..." style="width: 100%; padding: 8px;" />
        </div>
        
        <div style="margin-bottom: 15px;">
            <label for="sms_message_text" style="display:block; margin-bottom: 5px;">Message: *</label>
            <textarea id="sms_message_text" name="sms_message_text" required rows="4" style="width: 100%; padding: 8px;"></textarea>
        </div>
        
        <div>
            <input type="submit" name="sms_submit_message" value="Post Message" style="padding: 10px 20px; cursor: pointer;" />
        </div>
    </form>
    <?php
    return ob_get_clean();
}

/**
 * 4. Frontend Slideshow Shortcode: [message_slideshow]
 */
add_shortcode( 'message_slideshow', 'sms_slideshow_shortcode' );
function sms_slideshow_shortcode() {
    // Query published messages
    $query = new WP_Query( array(
        'post_type'      => 'sms_message',
        'post_status'    => 'publish',
        'posts_per_page' => -1, // Get all messages
        'orderby'        => 'date',
        'order'          => 'DESC'
    ) );

    if ( ! $query->have_posts() ) {
        return '<p>No messages to display yet.</p>';
    }

    $slideshow_id = 'sms-slideshow-' . uniqid();
    ob_start();
    ?>
    
    <!-- Slideshow CSS -->
    <style>
        .sms-slideshow-container {
            position: relative;
            max-width: 700px;
            margin: 30px auto;
            border-radius: 8px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            padding: 40px 20px;
            text-align: center;
            min-height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .sms-slide {
            width: 100%;
            animation: smsFade 1s ease-in-out;
        }
        .sms-message-text {
            font-size: 1.2em;
            font-style: italic;
            color: #333;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        .sms-message-author {
            font-weight: bold;
            color: #555;
            font-size: 1em;
        }
        .sms-message-author a {
            text-decoration: none;
            color: #0073aa;
        }
        .sms-message-author a:hover {
            text-decoration: underline;
        }
        @keyframes smsFade {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <!-- Slideshow HTML -->
    <div id="<?php echo esc_attr( $slideshow_id ); ?>" class="sms-slideshow-container">
        <?php $i = 0; while ( $query->have_posts() ) : $query->the_post(); 
            $link   = get_post_meta( get_the_ID(), '_sms_website_link', true );
            $author = get_the_title();
            $content = get_the_content();
        ?>
            <div class="sms-slide" style="display: <?php echo $i === 0 ? 'block' : 'none'; ?>;">
                <div class="sms-message-text">"<?php echo wp_kses_post( $content ); ?>"</div>
                <div class="sms-message-author">
                    &mdash; 
                    <?php if ( $link ) : ?>
                        <a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener noreferrer">
                            <?php echo esc_html( $author ); ?>
                        </a>
                    <?php else : ?>
                        <?php echo esc_html( $author ); ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php $i++; endwhile; wp_reset_postdata(); ?>
    </div>
    
    <!-- Slideshow JavaScript -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var container = document.getElementById("<?php echo esc_js( $slideshow_id ); ?>");
            if ( !container ) return;
            
            var slides = container.querySelectorAll(".sms-slide");
            var currentSlide = 0;
            
            if ( slides.length <= 1 ) return; // Skip logic if there's only 1 message

            setInterval(function() {
                // Hide current
                slides[currentSlide].style.display = "none";
                
                // Move to next
                currentSlide++;
                if ( currentSlide >= slides.length ) {
                    currentSlide = 0;
                }
                
                // Show new
                slides[currentSlide].style.display = "block";
            }, 5000); // 5000ms = 5 seconds per slide
        });
    </script>
    <?php
    return ob_get_clean();
}
```
