Here's the complete single-file WordPress testimonials plugin:

```php
<?php
/**
 * Plugin Name: Simple Testimonials
 * Plugin URI: https://example.com
 * Description: A simple plugin for collecting and displaying visitor testimonials
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: simple-testimonials
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Simple_Testimonials {
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_submit_testimonial', array( $this, 'handle_testimonial_submission' ) );
		add_action( 'wp_ajax_nopriv_submit_testimonial', array( $this, 'handle_testimonial_submission' ) );
	}

	public function register_post_type() {
		register_post_type( 'testimonial', array(
			'labels' => array(
				'name'          => __( 'Testimonials', 'simple-testimonials' ),
				'singular_name' => __( 'Testimonial', 'simple-testimonials' ),
			),
			'public'              => false,
			'show_in_rest'        => true,
			'supports'            => array( 'title', 'editor' ),
			'has_archive'         => false,
			'exclude_from_search' => true,
		) );
	}

	public function register_shortcodes() {
		add_shortcode( 'testimonial_form', array( $this, 'render_form' ) );
		add_shortcode( 'testimonials_display', array( $this, 'render_testimonials' ) );
	}

	public function enqueue_scripts() {
		if ( is_singular() || is_home() ) {
			wp_localize_script( 'jquery', 'testimonialsAjax', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'testimonial_nonce' ),
			) );
		}
	}

	public function render_form() {
		ob_start();
		?>
		<div class="testimonial-form-wrapper">
			<form id="testimonial-form" class="testimonial-form">
				<div class="form-group">
					<label for="testimonial_name"><?php esc_html_e( 'Your Name', 'simple-testimonials' ); ?></label>
					<input
						type="text"
						id="testimonial_name"
						name="name"
						required
						placeholder="<?php esc_attr_e( 'Enter your name', 'simple-testimonials' ); ?>"
						maxlength="100"
					>
				</div>

				<div class="form-group">
					<label for="testimonial_email"><?php esc_html_e( 'Your Email', 'simple-testimonials' ); ?></label>
					<input
						type="email"
						id="testimonial_email"
						name="email"
						required
						placeholder="<?php esc_attr_e( 'Enter your email', 'simple-testimonials' ); ?>"
						maxlength="100"
					>
				</div>

				<div class="form-group">
					<label for="testimonial_text"><?php esc_html_e( 'Your Testimonial', 'simple-testimonials' ); ?></label>
					<textarea
						id="testimonial_text"
						name="text"
						required
						placeholder="<?php esc_attr_e( 'Share your experience...', 'simple-testimonials' ); ?>"
						maxlength="500"
						rows="5"
					></textarea>
					<small class="char-count">0/500</small>
				</div>

				<button type="submit" class="submit-btn"><?php esc_html_e( 'Submit Testimonial', 'simple-testimonials' ); ?></button>
				<div id="form-message" class="form-message"></div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	public function render_testimonials() {
		$testimonials = get_posts( array(
			'post_type'      => 'testimonial',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );

		ob_start();
		?>
		<div class="testimonials-wrapper">
			<?php if ( ! empty( $testimonials ) ) : ?>
				<div class="testimonials-grid">
					<?php foreach ( $testimonials as $testimonial ) : ?>
						<div class="testimonial-item">
							<div class="testimonial-text">
								<?php echo wp_kses_post( $testimonial->post_content ); ?>
							</div>
							<div class="testimonial-author">
								<?php echo esc_html( $testimonial->post_title ); ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="no-testimonials"><?php esc_html_e( 'No testimonials yet. Be the first to share!', 'simple-testimonials' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	public function handle_testimonial_submission() {
		check_ajax_referer( 'testimonial_nonce', 'nonce' );

		$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$text  = isset( $_POST['text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['text'] ) ) : '';

		if ( empty( $name ) || empty( $email ) || empty( $text ) ) {
			wp_send_json_error( array(
				'message' => __( 'Please fill in all fields.', 'simple-testimonials' ),
			) );
		}

		if ( ! is_email( $email ) ) {
			wp_send_json_error( array(
				'message' => __( 'Please enter a valid email address.', 'simple-testimonials' ),
			) );
		}

		$post_id = wp_insert_post( array(
			'post_type'    => 'testimonial',
			'post_title'   => $name,
			'post_content' => $text,
			'post_status'  => 'publish',
			'meta_input'   => array(
				'_testimonial_email' => $email,
			),
		) );

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array(
				'message' => __( 'There was an error saving your testimonial. Please try again.', 'simple-testimonials' ),
			) );
		}

		wp_send_json_success( array(
			'message' => __( 'Thank you! Your testimonial has been submitted.', 'simple-testimonials' ),
		) );
	}
}

new Simple_Testimonials();

add_action( 'wp_head', function() {
	?>
	<style>
		.testimonial-form-wrapper {
			max-width: 500px;
			margin: 20px auto;
			padding: 20px;
			background: #f9f9f9;
			border-radius: 8px;
			box-shadow: 0 2px 4px rgba(0,0,0,0.1);
		}

		.testimonial-form {
			display: flex;
			flex-direction: column;
		}

		.form-group {
			margin-bottom: 15px;
		}

		.form-group label {
			display: block;
			margin-bottom: 5px;
			font-weight: 600;
			color: #333;
		}

		.form-group input,
		.form-group textarea {
			width: 100%;
			padding: 10px;
			border: 1px solid #ddd;
			border-radius: 4px;
			font-family: inherit;
			font-size: 14px;
			box-sizing: border-box;
		}

		.form-group input:focus,
		.form-group textarea:focus {
			outline: none;
			border-color: #0073aa;
			box-shadow: 0 0 0 3px rgba(0,115,170,0.1);
		}

		.form-group small {
			display: block;
			margin-top: 5px;
			color: #999;
			font-size: 12px;
		}

		.submit-btn {
			padding: 12px 20px;
			background: #0073aa;
			color: white;
			border: none;
			border-radius: 4px;
			font-weight: 600;
			cursor: pointer;
			transition: background 0.3s;
		}

		.submit-btn:hover {
			background: #005a87;
		}

		.submit-btn:disabled {
			background: #ccc;
			cursor: not-allowed;
		}

		.form-message {
			margin-top: 15px;
			padding: 10px;
			border-radius: 4px;
			text-align: center;
			display: none;
		}

		.form-message.success {
			background: #d4edda;
			color: #155724;
			border: 1px solid #c3e6cb;
			display: block;
		}

		.form-message.error {
			background: #f8d7da;
			color: #721c24;
			border: 1px solid #f5c6cb;
			display: block;
		}

		.testimonials-wrapper {
			max-width: 900px;
			margin: 30px auto;
			padding: 20px;
		}

		.testimonials-grid {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
			gap: 20px;
		}

		.testimonial-item {
			padding: 20px;
			background: #f9f9f9;
			border-left: 4px solid #0073aa;
			border-radius: 4px;
			box-shadow: 0 2px 4px rgba(0,0,0,0.1);
		}

		.testimonial-text {
			margin-bottom: 15px;
			color: #555;
			line-height: 1.6;
			font-style: italic;
		}

		.testimonial-author {
			color: #333;
			font-weight: 600;
			text-align: right;
		}

		.no-testimonials {
			text-align: center;
			color: #999;
			font-size: 16px;
			padding: 40px 20px;
		}

		@media (max-width: 600px) {
			.testimonial-form-wrapper {
				padding: 15px;
			}

			.testimonials-grid {
				grid-template-columns: 1fr;
			}
		}
	</style>
	<?php
});

add_action( 'wp_footer', function() {
	?>
	<script>
		(function($) {
			var form = $('#testimonial-form');
			if (!form.length) return;

			var textarea = form.find('#testimonial_text');

			textarea.on('input', function() {
				var count = $(this).val().length;
				form.find('.char-count').text(count + '/500');
			});

			form.on('submit', function(e) {
				e.preventDefault();

				var btn = form.find('.submit-btn');
				var message = form.find('#form-message');

				btn.prop('disabled', true).text('Submitting...');
				message.removeClass('success error').hide();

				$.ajax({
					type: 'POST',
					url: testimonialsAjax.ajaxurl,
					data: {
						action: 'submit_testimonial',
						nonce: testimonialsAjax.nonce,
						name: form.find('#testimonial_name').val(),
						email: form.find('#testimonial_email').val(),
						text: form.find('#testimonial_text').val(),
					},
					success: function(response) {
						if (response.success) {
							message.addClass('success').text(response.data.message).show();
							form[0].reset();
							form.find('.char-count').text('0/500');
							setTimeout(function() {
								location.reload();
							}, 2000);
						} else {
							message.addClass('error').text(response.data.message).show();
						}
					},
					error: function() {
						message.addClass('error').text('An error occurred. Please try again.').show();
					},
					complete: function() {
						btn.prop('disabled', false).text('Submit Testimonial');
					}
				});
			});
		})(jQuery);
	</script>
	<?php
});
?>
```

## How to use this plugin:

1. **Copy the code** into a new file called `testimonials-plugin.php`
2. **Upload to WordPress** at `/wp-content/plugins/`
3. **Activate** the plugin in the WordPress admin
4. **Add to your pages** using these shortcodes:
   - `[testimonial_form]` – displays the submission form
   - `[testimonials_display]` – shows all submitted testimonials

## Features:

- **Single-file plugin** – everything in one file (CSS, JS, PHP)
- **Form validation** – validates name, email format, and required fields
- **Character counter** – shows count up to 500 char limit
- **AJAX submission** – no page reload needed
- **Security** – includes nonce verification and proper sanitization
- **Custom post type** – stores testimonials in WordPress database
- **Responsive grid** – testimonials display in a responsive card layout
- **Success/error messages** – user feedback on form submission
- **Email storage** – saves submitter email as post meta (private)
