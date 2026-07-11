OpenAI Codex v0.142.3
--------
workdir: /private/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.15XkRiXbsx
model: gpt-5.5
provider: openai
approval: never
sandbox: read-only
reasoning effort: xhigh
reasoning summaries: none
session id: 019f138e-b692-7440-9a82-f60f2ea0f9d6
--------
user
Create a single-file WordPress plugin that lets visitors leave a short text testimonial through a form, saves the testimonials, and shows them on a page. Show me the full plugin code.
web search: 
web search: site:developer.wordpress.org add_shortcode wp_insert_post sanitize_textarea_field wp_nonce_field WordPress developer documentation
web search: 
web search: developer.wordpress.org add_shortcode
web search: 
web search: https://developer.wordpress.org/reference/functions/add_shortcode/
codex
Use shortcode `[visitor_testimonials]` on the page. New submissions are saved as `Pending` testimonials; publish them in WP Admin to show them publicly.

```php
<?php
/**
 * Plugin Name: Visitor Testimonials
 * Description: Front-end testimonial form and display shortcode.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 * Text Domain: visitor-testimonials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Visitor_Testimonials_Plugin {
	const POST_TYPE              = 'vst_testimonial';
	const NONCE_ACTION           = 'vst_submit_testimonial';
	const NONCE_NAME             = 'vst_nonce';
	const MAX_NAME_LENGTH        = 80;
	const MAX_TESTIMONIAL_LENGTH = 500;

	private static $printed_styles = false;

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_shortcode( 'visitor_testimonials', array( __CLASS__, 'render_shortcode' ) );

		add_action( 'admin_post_nopriv_vst_submit_testimonial', array( __CLASS__, 'handle_submission' ) );
		add_action( 'admin_post_vst_submit_testimonial', array( __CLASS__, 'handle_submission' ) );

		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'add_admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'render_admin_columns' ), 10, 2 );
	}

	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'          => __( 'Testimonials', 'visitor-testimonials' ),
					'singular_name' => __( 'Testimonial', 'visitor-testimonials' ),
					'menu_name'     => __( 'Testimonials', 'visitor-testimonials' ),
					'add_new_item'  => __( 'Add New Testimonial', 'visitor-testimonials' ),
					'edit_item'     => __( 'Edit Testimonial', 'visitor-testimonials' ),
					'search_items'  => __( 'Search Testimonials', 'visitor-testimonials' ),
					'not_found'     => __( 'No testimonials found.', 'visitor-testimonials' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-format-quote',
				'supports'     => array( 'title', 'editor' ),
				'map_meta_cap' => true,
			)
		);
	}

	public static function handle_submission() {
		$referer  = wp_get_referer() ? wp_get_referer() : home_url( '/' );
		$redirect = remove_query_arg( array( 'vst_submitted', 'vst_error' ), $referer );

		$nonce = isset( $_POST[ self::NONCE_NAME ] )
			? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) )
			: '';

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			self::redirect_with_status( $redirect, 'invalid' );
		}

		$honeypot = isset( $_POST['vst_website'] )
			? trim( sanitize_text_field( wp_unslash( $_POST['vst_website'] ) ) )
			: '';

		if ( '' !== $honeypot ) {
			self::redirect_with_status( $redirect, 'success' );
		}

		$name = isset( $_POST['vst_name'] )
			? sanitize_text_field( wp_unslash( $_POST['vst_name'] ) )
			: '';

		$testimonial = isset( $_POST['vst_testimonial'] )
			? sanitize_textarea_field( wp_unslash( $_POST['vst_testimonial'] ) )
			: '';

		$name        = self::limit_chars( $name, self::MAX_NAME_LENGTH );
		$testimonial = self::limit_chars( $testimonial, self::MAX_TESTIMONIAL_LENGTH );

		if ( '' === trim( $testimonial ) ) {
			self::redirect_with_status( $redirect, 'missing' );
		}

		if ( '' === trim( $name ) ) {
			$name = __( 'Anonymous', 'visitor-testimonials' );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'pending',
				'post_title'     => sprintf( __( 'Testimonial from %s', 'visitor-testimonials' ), $name ),
				'post_content'   => $testimonial,
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
				'meta_input'     => array(
					'_vst_author_name' => $name,
				),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			self::redirect_with_status( $redirect, 'save' );
		}

		self::redirect_with_status( $redirect, 'success' );
	}

	public static function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'     => 10,
				'show_form' => 'yes',
			),
			$atts,
			'visitor_testimonials'
		);

		$limit = absint( $atts['limit'] );
		$limit = $limit ? min( $limit, 50 ) : 10;

		$show_form = in_array(
			strtolower( (string) $atts['show_form'] ),
			array( '1', 'true', 'yes', 'on' ),
			true
		);

		$testimonials = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'numberposts'    => $limit,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);

		$form_id = wp_unique_id( 'vst-form-' );
		$name_id = $form_id . '-name';
		$text_id = $form_id . '-testimonial';

		ob_start();
		?>
		<div class="vst-testimonials">
			<?php self::render_styles(); ?>
			<?php self::render_notice(); ?>

			<?php if ( $show_form ) : ?>
				<form class="vst-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="vst_submit_testimonial">
					<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>

					<div class="vst-honeypot" aria-hidden="true">
						<label>
							<?php esc_html_e( 'Website', 'visitor-testimonials' ); ?>
							<input type="text" name="vst_website" tabindex="-1" autocomplete="off">
						</label>
					</div>

					<p class="vst-field">
						<label for="<?php echo esc_attr( $name_id ); ?>"><?php esc_html_e( 'Name', 'visitor-testimonials' ); ?></label>
						<input id="<?php echo esc_attr( $name_id ); ?>" type="text" name="vst_name" maxlength="<?php echo esc_attr( self::MAX_NAME_LENGTH ); ?>" autocomplete="name">
					</p>

					<p class="vst-field">
						<label for="<?php echo esc_attr( $text_id ); ?>"><?php esc_html_e( 'Testimonial', 'visitor-testimonials' ); ?></label>
						<textarea id="<?php echo esc_attr( $text_id ); ?>" name="vst_testimonial" rows="5" maxlength="<?php echo esc_attr( self::MAX_TESTIMONIAL_LENGTH ); ?>" required></textarea>
					</p>

					<button class="vst-submit" type="submit"><?php esc_html_e( 'Submit Testimonial', 'visitor-testimonials' ); ?></button>
				</form>
			<?php endif; ?>

			<div class="vst-list">
				<h2 class="vst-heading"><?php esc_html_e( 'Testimonials', 'visitor-testimonials' ); ?></h2>

				<?php if ( $testimonials ) : ?>
					<?php foreach ( $testimonials as $testimonial ) : ?>
						<?php
						$author = get_post_meta( $testimonial->ID, '_vst_author_name', true );
						$author = $author ? $author : __( 'Anonymous', 'visitor-testimonials' );
						?>
						<figure class="vst-item">
							<blockquote><?php echo wpautop( esc_html( $testimonial->post_content ) ); ?></blockquote>
							<figcaption><?php echo esc_html( $author ); ?></figcaption>
						</figure>
					<?php endforeach; ?>
				<?php else : ?>
					<p class="vst-empty"><?php esc_html_e( 'No testimonials have been published yet.', 'visitor-testimonials' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	public static function add_admin_columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;

			if ( 'title' === $key ) {
				$new_columns['vst_author'] = __( 'Visitor Name', 'visitor-testimonials' );
			}
		}

		return $new_columns;
	}

	public static function render_admin_columns( $column, $post_id ) {
		if ( 'vst_author' !== $column ) {
			return;
		}

		$name = get_post_meta( $post_id, '_vst_author_name', true );
		echo esc_html( $name ? $name : __( 'Anonymous', 'visitor-testimonials' ) );
	}

	private static function render_notice() {
		if ( isset( $_GET['vst_submitted'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['vst_submitted'] ) ) ) {
			echo '<div class="vst-notice" role="status">' . esc_html__( 'Thanks. Your testimonial was submitted and is awaiting review.', 'visitor-testimonials' ) . '</div>';
			return;
		}

		if ( ! isset( $_GET['vst_error'] ) ) {
			return;
		}

		$error = sanitize_key( wp_unslash( $_GET['vst_error'] ) );

		$messages = array(
			'invalid' => __( 'The form expired. Please try again.', 'visitor-testimonials' ),
			'missing' => __( 'Please enter a testimonial before submitting.', 'visitor-testimonials' ),
			'save'    => __( 'Sorry, your testimonial could not be saved. Please try again.', 'visitor-testimonials' ),
		);

		$message = isset( $messages[ $error ] )
			? $messages[ $error ]
			: __( 'Something went wrong. Please try again.', 'visitor-testimonials' );

		echo '<div class="vst-notice vst-notice-error" role="alert">' . esc_html( $message ) . '</div>';
	}

	private static function render_styles() {
		if ( self::$printed_styles ) {
			return;
		}

		self::$printed_styles = true;
		?>
		<style>
			.vst-testimonials { margin: 1.5rem 0; }
			.vst-form { border: 1px solid #ddd; border-radius: 6px; padding: 1rem; margin-bottom: 1.5rem; }
			.vst-field { margin: 0 0 1rem; }
			.vst-field label { display: block; font-weight: 600; margin-bottom: .35rem; }
			.vst-field input[type="text"],
			.vst-field textarea { width: 100%; max-width: 100%; box-sizing: border-box; }
			.vst-submit { cursor: pointer; }
			.vst-list { display: grid; gap: 1rem; }
			.vst-heading { margin-top: 0; }
			.vst-item { margin: 0; padding: 1rem; border-left: 4px solid #2271b1; background: rgba(0, 0, 0, .035); }
			.vst-item blockquote { margin: 0; }
			.vst-item figcaption { margin-top: .75rem; font-weight: 600; }
			.vst-notice { padding: .75rem 1rem; margin-bottom: 1rem; border-left: 4px solid #2271b1; background: #f0f6fc; }
			.vst-notice-error { border-left-color: #d63638; background: #fcf0f1; }
			.vst-honeypot { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }
		</style>
		<?php
	}

	private static function redirect_with_status( $url, $status ) {
		$url = ( 'success' === $status )
			? add_query_arg( 'vst_submitted', '1', $url )
			: add_query_arg( 'vst_error', $status, $url );

		wp_safe_redirect( $url );
		exit;
	}

	private static function limit_chars( $value, $limit ) {
		$value = trim( (string) $value );

		if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
			return mb_strlen( $value ) > $limit ? mb_substr( $value, 0, $limit ) : $value;
		}

		return strlen( $value ) > $limit ? substr( $value, 0, $limit ) : $value;
	}
}

Visitor_Testimonials_Plugin::init();
```

References used: WordPress docs for `add_shortcode()`, `wp_insert_post()`, and `sanitize_textarea_field()`.
tokens used
21 272
Use shortcode `[visitor_testimonials]` on the page. New submissions are saved as `Pending` testimonials; publish them in WP Admin to show them publicly.

```php
<?php
/**
 * Plugin Name: Visitor Testimonials
 * Description: Front-end testimonial form and display shortcode.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 * Text Domain: visitor-testimonials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Visitor_Testimonials_Plugin {
	const POST_TYPE              = 'vst_testimonial';
	const NONCE_ACTION           = 'vst_submit_testimonial';
	const NONCE_NAME             = 'vst_nonce';
	const MAX_NAME_LENGTH        = 80;
	const MAX_TESTIMONIAL_LENGTH = 500;

	private static $printed_styles = false;

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_shortcode( 'visitor_testimonials', array( __CLASS__, 'render_shortcode' ) );

		add_action( 'admin_post_nopriv_vst_submit_testimonial', array( __CLASS__, 'handle_submission' ) );
		add_action( 'admin_post_vst_submit_testimonial', array( __CLASS__, 'handle_submission' ) );

		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'add_admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'render_admin_columns' ), 10, 2 );
	}

	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'          => __( 'Testimonials', 'visitor-testimonials' ),
					'singular_name' => __( 'Testimonial', 'visitor-testimonials' ),
					'menu_name'     => __( 'Testimonials', 'visitor-testimonials' ),
					'add_new_item'  => __( 'Add New Testimonial', 'visitor-testimonials' ),
					'edit_item'     => __( 'Edit Testimonial', 'visitor-testimonials' ),
					'search_items'  => __( 'Search Testimonials', 'visitor-testimonials' ),
					'not_found'     => __( 'No testimonials found.', 'visitor-testimonials' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-format-quote',
				'supports'     => array( 'title', 'editor' ),
				'map_meta_cap' => true,
			)
		);
	}

	public static function handle_submission() {
		$referer  = wp_get_referer() ? wp_get_referer() : home_url( '/' );
		$redirect = remove_query_arg( array( 'vst_submitted', 'vst_error' ), $referer );

		$nonce = isset( $_POST[ self::NONCE_NAME ] )
			? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) )
			: '';

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			self::redirect_with_status( $redirect, 'invalid' );
		}

		$honeypot = isset( $_POST['vst_website'] )
			? trim( sanitize_text_field( wp_unslash( $_POST['vst_website'] ) ) )
			: '';

		if ( '' !== $honeypot ) {
			self::redirect_with_status( $redirect, 'success' );
		}

		$name = isset( $_POST['vst_name'] )
			? sanitize_text_field( wp_unslash( $_POST['vst_name'] ) )
			: '';

		$testimonial = isset( $_POST['vst_testimonial'] )
			? sanitize_textarea_field( wp_unslash( $_POST['vst_testimonial'] ) )
			: '';

		$name        = self::limit_chars( $name, self::MAX_NAME_LENGTH );
		$testimonial = self::limit_chars( $testimonial, self::MAX_TESTIMONIAL_LENGTH );

		if ( '' === trim( $testimonial ) ) {
			self::redirect_with_status( $redirect, 'missing' );
		}

		if ( '' === trim( $name ) ) {
			$name = __( 'Anonymous', 'visitor-testimonials' );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'pending',
				'post_title'     => sprintf( __( 'Testimonial from %s', 'visitor-testimonials' ), $name ),
				'post_content'   => $testimonial,
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
				'meta_input'     => array(
					'_vst_author_name' => $name,
				),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			self::redirect_with_status( $redirect, 'save' );
		}

		self::redirect_with_status( $redirect, 'success' );
	}

	public static function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'     => 10,
				'show_form' => 'yes',
			),
			$atts,
			'visitor_testimonials'
		);

		$limit = absint( $atts['limit'] );
		$limit = $limit ? min( $limit, 50 ) : 10;

		$show_form = in_array(
			strtolower( (string) $atts['show_form'] ),
			array( '1', 'true', 'yes', 'on' ),
			true
		);

		$testimonials = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'numberposts'    => $limit,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);

		$form_id = wp_unique_id( 'vst-form-' );
		$name_id = $form_id . '-name';
		$text_id = $form_id . '-testimonial';

		ob_start();
		?>
		<div class="vst-testimonials">
			<?php self::render_styles(); ?>
			<?php self::render_notice(); ?>

			<?php if ( $show_form ) : ?>
				<form class="vst-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="vst_submit_testimonial">
					<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>

					<div class="vst-honeypot" aria-hidden="true">
						<label>
							<?php esc_html_e( 'Website', 'visitor-testimonials' ); ?>
							<input type="text" name="vst_website" tabindex="-1" autocomplete="off">
						</label>
					</div>

					<p class="vst-field">
						<label for="<?php echo esc_attr( $name_id ); ?>"><?php esc_html_e( 'Name', 'visitor-testimonials' ); ?></label>
						<input id="<?php echo esc_attr( $name_id ); ?>" type="text" name="vst_name" maxlength="<?php echo esc_attr( self::MAX_NAME_LENGTH ); ?>" autocomplete="name">
					</p>

					<p class="vst-field">
						<label for="<?php echo esc_attr( $text_id ); ?>"><?php esc_html_e( 'Testimonial', 'visitor-testimonials' ); ?></label>
						<textarea id="<?php echo esc_attr( $text_id ); ?>" name="vst_testimonial" rows="5" maxlength="<?php echo esc_attr( self::MAX_TESTIMONIAL_LENGTH ); ?>" required></textarea>
					</p>

					<button class="vst-submit" type="submit"><?php esc_html_e( 'Submit Testimonial', 'visitor-testimonials' ); ?></button>
				</form>
			<?php endif; ?>

			<div class="vst-list">
				<h2 class="vst-heading"><?php esc_html_e( 'Testimonials', 'visitor-testimonials' ); ?></h2>

				<?php if ( $testimonials ) : ?>
					<?php foreach ( $testimonials as $testimonial ) : ?>
						<?php
						$author = get_post_meta( $testimonial->ID, '_vst_author_name', true );
						$author = $author ? $author : __( 'Anonymous', 'visitor-testimonials' );
						?>
						<figure class="vst-item">
							<blockquote><?php echo wpautop( esc_html( $testimonial->post_content ) ); ?></blockquote>
							<figcaption><?php echo esc_html( $author ); ?></figcaption>
						</figure>
					<?php endforeach; ?>
				<?php else : ?>
					<p class="vst-empty"><?php esc_html_e( 'No testimonials have been published yet.', 'visitor-testimonials' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	public static function add_admin_columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;

			if ( 'title' === $key ) {
				$new_columns['vst_author'] = __( 'Visitor Name', 'visitor-testimonials' );
			}
		}

		return $new_columns;
	}

	public static function render_admin_columns( $column, $post_id ) {
		if ( 'vst_author' !== $column ) {
			return;
		}

		$name = get_post_meta( $post_id, '_vst_author_name', true );
		echo esc_html( $name ? $name : __( 'Anonymous', 'visitor-testimonials' ) );
	}

	private static function render_notice() {
		if ( isset( $_GET['vst_submitted'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['vst_submitted'] ) ) ) {
			echo '<div class="vst-notice" role="status">' . esc_html__( 'Thanks. Your testimonial was submitted and is awaiting review.', 'visitor-testimonials' ) . '</div>';
			return;
		}

		if ( ! isset( $_GET['vst_error'] ) ) {
			return;
		}

		$error = sanitize_key( wp_unslash( $_GET['vst_error'] ) );

		$messages = array(
			'invalid' => __( 'The form expired. Please try again.', 'visitor-testimonials' ),
			'missing' => __( 'Please enter a testimonial before submitting.', 'visitor-testimonials' ),
			'save'    => __( 'Sorry, your testimonial could not be saved. Please try again.', 'visitor-testimonials' ),
		);

		$message = isset( $messages[ $error ] )
			? $messages[ $error ]
			: __( 'Something went wrong. Please try again.', 'visitor-testimonials' );

		echo '<div class="vst-notice vst-notice-error" role="alert">' . esc_html( $message ) . '</div>';
	}

	private static function render_styles() {
		if ( self::$printed_styles ) {
			return;
		}

		self::$printed_styles = true;
		?>
		<style>
			.vst-testimonials { margin: 1.5rem 0; }
			.vst-form { border: 1px solid #ddd; border-radius: 6px; padding: 1rem; margin-bottom: 1.5rem; }
			.vst-field { margin: 0 0 1rem; }
			.vst-field label { display: block; font-weight: 600; margin-bottom: .35rem; }
			.vst-field input[type="text"],
			.vst-field textarea { width: 100%; max-width: 100%; box-sizing: border-box; }
			.vst-submit { cursor: pointer; }
			.vst-list { display: grid; gap: 1rem; }
			.vst-heading { margin-top: 0; }
			.vst-item { margin: 0; padding: 1rem; border-left: 4px solid #2271b1; background: rgba(0, 0, 0, .035); }
			.vst-item blockquote { margin: 0; }
			.vst-item figcaption { margin-top: .75rem; font-weight: 600; }
			.vst-notice { padding: .75rem 1rem; margin-bottom: 1rem; border-left: 4px solid #2271b1; background: #f0f6fc; }
			.vst-notice-error { border-left-color: #d63638; background: #fcf0f1; }
			.vst-honeypot { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }
		</style>
		<?php
	}

	private static function redirect_with_status( $url, $status ) {
		$url = ( 'success' === $status )
			? add_query_arg( 'vst_submitted', '1', $url )
			: add_query_arg( 'vst_error', $status, $url );

		wp_safe_redirect( $url );
		exit;
	}

	private static function limit_chars( $value, $limit ) {
		$value = trim( (string) $value );

		if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
			return mb_strlen( $value ) > $limit ? mb_substr( $value, 0, $limit ) : $value;
		}

		return strlen( $value ) > $limit ? substr( $value, 0, $limit ) : $value;
	}
}

Visitor_Testimonials_Plugin::init();
```

References used: WordPress docs for `add_shortcode()`, `wp_insert_post()`, and `sanitize_textarea_field()`.
