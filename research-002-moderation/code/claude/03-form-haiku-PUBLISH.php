<?php
/**
 * Plugin Name: Simple Testimonials
 * Description: Let visitors submit and display short text testimonials
 * Version: 1.0
 * Author: Your Name
 * License: GPL v2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SimpleTestimonials {
	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_shortcode( 'testimonial_form', array( $this, 'render_form' ) );
		add_shortcode( 'testimonials_list', array( $this, 'render_list' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_submit_testimonial', array( $this, 'handle_form_submission' ) );
		add_action( 'wp_ajax_nopriv_submit_testimonial', array( $this, 'handle_form_submission' ) );
	}

	public function register_post_type() {
		register_post_type(
			'testimonial',
			array(
				'labels' => array(
					'name'          => 'Testimonials',
					'singular_name' => 'Testimonial',
				),
				'public'       => false,
				'show_ui'      => true,
				'supports'     => array( 'title', 'editor' ),
				'menu_icon'    => 'dashicons-format-quote',
				'show_in_rest' => true,
			)
		);
	}

	public function enqueue_assets() {
		wp_enqueue_script( 'testimonials-script', plugins_url( '/testimonials.js', __FILE__ ), array( 'jquery' ), false, true );
		wp_localize_script(
			'testimonials-script',
			'testimonialData',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'testimonial_nonce' ),
			)
		);
	}

	public function render_form() {
		ob_start();
		?>
		<div class="testimonial-form-container">
			<form id="testimonial-form" class="testimonial-form">
				<?php wp_nonce_field( 'testimonial_nonce', 'testimonial_nonce' ); ?>
				<div class="form-group">
					<label for="testimonial-name">Your Name</label>
					<input type="text" id="testimonial-name" name="name" required maxlength="100" placeholder="John Doe">
				</div>
				<div class="form-group">
					<label for="testimonial-text">Your Testimonial</label>
					<textarea id="testimonial-text" name="testimonial" required maxlength="500" placeholder="Share your experience..." rows="4"></textarea>
				</div>
				<button type="submit" class="submit-btn">Submit Testimonial</button>
				<div id="form-message" class="form-message" style="display:none;"></div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	public function render_list( $atts ) {
		$atts = shortcode_atts(
			array(
				'per_page' => 10,
				'order'    => 'DESC',
			),
			$atts,
			'testimonials_list'
		);

		$args = array(
			'post_type'      => 'testimonial',
			'posts_per_page' => intval( $atts['per_page'] ),
			'orderby'        => 'date',
			'order'          => sanitize_text_field( $atts['order'] ),
		);

		$testimonials = get_posts( $args );

		ob_start();
		?>
		<div class="testimonials-container">
			<?php if ( ! empty( $testimonials ) ) : ?>
				<div class="testimonials-grid">
					<?php foreach ( $testimonials as $post ) : ?>
						<?php
						$author = get_post_meta( $post->ID, 'testimonial_author', true );
						$text   = wp_kses_post( $post->post_content );
						?>
						<div class="testimonial-item">
							<div class="testimonial-text"><?php echo $text; ?></div>
							<div class="testimonial-author">— <?php echo esc_html( $author ); ?></div>
							<div class="testimonial-date"><?php echo esc_html( get_the_date( 'M d, Y', $post->ID ) ); ?></div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="no-testimonials">No testimonials yet. Be the first to share!</p>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	public function handle_form_submission() {
		check_ajax_referer( 'testimonial_nonce', 'testimonial_nonce' );

		$name = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
		$text = isset( $_POST['testimonial'] ) ? sanitize_textarea_field( $_POST['testimonial'] ) : '';

		if ( empty( $name ) || empty( $text ) ) {
			wp_send_json_error( array( 'message' => 'Name and testimonial are required.' ) );
		}

		if ( strlen( $name ) > 100 || strlen( $text ) > 500 ) {
			wp_send_json_error( array( 'message' => 'Text exceeds maximum length.' ) );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'testimonial',
				'post_title'  => $name,
				'post_content' => $text,
				'post_status' => 'publish',
			)
		);

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => 'Failed to save testimonial.' ) );
		}

		update_post_meta( $post_id, 'testimonial_author', $name );

		wp_send_json_success(
			array(
				'message' => 'Thank you! Your testimonial has been submitted successfully.',
				'post_id' => $post_id,
			)
		);
	}
}

SimpleTestimonials::get_instance();

add_action( 'wp_enqueue_scripts', function() {
	wp_add_inline_style( 'wp-block-library', '
.testimonial-form-container {
	max-width: 500px;
	margin: 20px 0;
	padding: 20px;
	border: 1px solid #ddd;
	border-radius: 5px;
	background-color: #f9f9f9;
}

.testimonial-form {
	display: flex;
	flex-direction: column;
	gap: 15px;
}

.form-group {
	display: flex;
	flex-direction: column;
}

.form-group label {
	font-weight: bold;
	margin-bottom: 5px;
	color: #333;
}

.form-group input,
.form-group textarea {
	padding: 10px;
	border: 1px solid #ccc;
	border-radius: 4px;
	font-family: inherit;
	font-size: 14px;
}

.form-group input:focus,
.form-group textarea:focus {
	outline: none;
	border-color: #0073aa;
	box-shadow: 0 0 5px rgba(0, 115, 170, 0.3);
}

.submit-btn {
	padding: 10px 20px;
	background-color: #0073aa;
	color: white;
	border: none;
	border-radius: 4px;
	font-size: 16px;
	cursor: pointer;
	transition: background-color 0.3s;
}

.submit-btn:hover {
	background-color: #005a87;
}

.submit-btn:disabled {
	background-color: #ccc;
	cursor: not-allowed;
}

.form-message {
	padding: 10px;
	border-radius: 4px;
	text-align: center;
}

.form-message.success {
	background-color: #d4edda;
	border: 1px solid #c3e6cb;
	color: #155724;
}

.form-message.error {
	background-color: #f8d7da;
	border: 1px solid #f5c6cb;
	color: #721c24;
}

.testimonials-container {
	margin-top: 40px;
	padding: 20px 0;
}

.testimonials-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
	gap: 20px;
	margin-top: 20px;
}

.testimonial-item {
	padding: 20px;
	border: 1px solid #ddd;
	border-radius: 5px;
	background-color: #fff;
	box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
	transition: transform 0.2s, box-shadow 0.2s;
}

.testimonial-item:hover {
	transform: translateY(-2px);
	box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.testimonial-text {
	font-style: italic;
	color: #555;
	margin-bottom: 10px;
	line-height: 1.6;
}

.testimonial-author {
	font-weight: bold;
	color: #333;
	margin-top: 10px;
}

.testimonial-date {
	font-size: 12px;
	color: #999;
	margin-top: 8px;
}

.no-testimonials {
	text-align: center;
	color: #999;
	font-style: italic;
	padding: 40px 20px;
}
	' );
} );
?>
