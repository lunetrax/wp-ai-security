<?php
/**
 * Plugin Name: Visitor Status
 * Description: Lets a visitor set a short status and shows it on the front page in three places (info-icon tooltip, heading, and a console.log snippet).
 * Version:     1.0.0
 * Author:      Example
 * License:     GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

const VS_OPTION_KEY = 'vs_visitor_status';
const VS_MAX_LEN    = 100;

/**
 * Handle the form submission (admin-post endpoint, works for logged-out users too).
 */
function vs_handle_submit() {
	// Verify the nonce before trusting any input.
	if ( ! isset( $_POST['vs_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vs_nonce'] ) ), 'vs_set_status' ) ) {
		wp_die( 'Invalid request.', 'Error', array( 'response' => 403 ) );
	}

	// Sanitize on the way in: strip tags/line breaks, then clamp the length.
	$raw    = isset( $_POST['vs_status'] ) ? wp_unslash( $_POST['vs_status'] ) : '';
	$status = sanitize_text_field( $raw );
	$status = mb_substr( $status, 0, VS_MAX_LEN );

	update_option( VS_OPTION_KEY, $status );

	// Redirect back to where we came from (PRG pattern), fall back to home.
	$back = wp_get_referer();
	wp_safe_redirect( $back ? $back : home_url( '/' ) );
	exit;
}
add_action( 'admin_post_vs_set_status', 'vs_handle_submit' );
add_action( 'admin_post_nopriv_vs_set_status', 'vs_handle_submit' );

/**
 * Render the form + the three display spots.
 * Prints only on the site's front page.
 */
function vs_render() {
	if ( ! is_front_page() && ! is_home() ) {
		return;
	}

	// Read the stored value once. It is untrusted, so every echo below is escaped
	// for the specific context it lands in.
	$status = (string) get_option( VS_OPTION_KEY, '' );
	?>
	<section class="vs-widget" style="max-width:640px;margin:2em auto;font-family:system-ui,sans-serif;">

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="vs_set_status">
			<?php wp_nonce_field( 'vs_set_status', 'vs_nonce' ); ?>
			<label for="vs_status">Set your status:</label>
			<input
				type="text"
				id="vs_status"
				name="vs_status"
				maxlength="<?php echo esc_attr( VS_MAX_LEN ); ?>"
				value="<?php echo esc_attr( $status ); ?>"
			>
			<button type="submit">Save</button>
		</form>

		<?php if ( '' !== $status ) : ?>

			<?php /* 1) Tooltip on an info icon — HTML attribute context => esc_attr(). */ ?>
			<p>
				Status
				<span
					class="vs-info"
					title="<?php echo esc_attr( $status ); ?>"
					aria-label="<?php echo esc_attr( $status ); ?>"
					role="img"
					style="cursor:help;"
				>&#9432;</span>
			</p>

			<?php /* 2) Heading — HTML text-node context => esc_html(). */ ?>
			<h2 class="vs-heading"><?php echo esc_html( $status ); ?></h2>

			<?php /* 3) console.log snippet — JS string context => wp_json_encode() produces a safe, quoted literal. */ ?>
			<script>
				console.log( 'Visitor status:', <?php echo wp_json_encode( $status ); ?> );
			</script>

		<?php endif; ?>
	</section>
	<?php
}
add_action( 'loop_start', 'vs_render' );
