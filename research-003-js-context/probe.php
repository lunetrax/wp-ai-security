<?php
/**
 * Research 003 — empirical seal for the </script>-breakout truth table.
 *
 * Runs a breakout payload through WordPress Core's real escaping functions and prints the
 * emitted bytes, so the design doc's claims rest on observed output, not reasoning alone.
 * No DB: loads the actual Core sources and shims only the environment functions the
 * escaping chain reaches (get_option for blog_charset, apply_filters for the js_escape
 * filter). Machine-local: adjust ABSPATH to a local WP checkout.
 *
 * Run:  php probe.php
 * Expected: esc_js and default wp_json_encode do NOT contain literal "</script";
 *           JSON_UNESCAPED_SLASHES alone DOES (breaks out); JSON_HEX_TAG|UNESCAPED_SLASHES
 *           does not (Core's by-design guard).
 */
define( 'ABSPATH', '/Users/dtprog/Documents/mvperearstid/wordpress/' );
define( 'WPINC', 'wp-includes' );

require ABSPATH . 'wp-includes/class-wp-error.php';
require ABSPATH . 'wp-includes/compat.php';
require ABSPATH . 'wp-includes/functions.php';
require ABSPATH . 'wp-includes/utf8.php';
require ABSPATH . 'wp-includes/kses.php';
require ABSPATH . 'wp-includes/formatting.php';

// Only the environment functions Core's escaping chain reaches out to (no DB, no plugins).
// functions.php require()s option.php, so the real get_option runs; we short-circuit its
// one lookup (blog_charset) through the pre_option filter, before it touches wp_installing()
// or $wpdb. That keeps the actual esc_js / _wp_specialchars / wp_json_encode code in play.
if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $tag, $value, ...$args ) {
		if ( 'pre_option' === $tag && isset( $args[0] ) && 'blog_charset' === $args[0] ) {
			return 'UTF-8';
		}
		return $value;
	}
}
if ( ! function_exists( 'apply_filters_ref_array' ) ) {
	function apply_filters_ref_array( $tag, $args ) { return $args[0]; }
}
if ( ! function_exists( 'has_filter' ) ) {
	function has_filter() { return false; }
}
if ( ! function_exists( 'wp_installing' ) ) {
	function wp_installing() { return false; }
}
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = null ) { return $text; }
}
if ( ! function_exists( 'wp_load_translations_early' ) ) {
	function wp_load_translations_early() {}
}
if ( ! function_exists( '_is_utf8_charset' ) ) {
	function _is_utf8_charset( $charset ) {
		return is_string( $charset ) && in_array( strtolower( str_replace( array( '-', '_' ), '', $charset ) ), array( 'utf8' ), true );
	}
}

// Benign stand-in for a hostile string. The only question that matters for the breakout is
// whether the literal byte sequence "</script" survives the escaping function; what a real
// attacker would append after it is irrelevant to that test.
$payload = '</script>BREAKOUT-MARKER';

$cases = array(
	'raw concatenation [no escaping]'                  => $payload,
	'esc_js()'                                         => esc_js( $payload ),
	'wp_json_encode() default flags'                   => wp_json_encode( $payload ),
	'wp_json_encode(JSON_UNESCAPED_SLASHES)'           => wp_json_encode( $payload, JSON_UNESCAPED_SLASHES ),
	'wp_json_encode(HEX_TAG|UNESCAPED_SLASHES) [core]' => wp_json_encode( $payload, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ),
);

printf( "WP core: %s   PHP: %s\npayload: %s\n\n", '7.0', PHP_VERSION, $payload );
printf( "%-50s | %-7s | %s\n", 'approach', 'breaks?', 'emitted bytes' );
printf( "%s\n", str_repeat( '-', 110 ) );

foreach ( $cases as $label => $out ) {
	// WHATWG script data state: the <script> element is terminated by the literal sequence
	// "</script"; character references are NOT decoded there. So the only thing that matters
	// is whether that literal byte sequence survives.
	$breaks = ( false !== stripos( $out, '</script' ) ) ? 'YES' : 'no';
	printf( "%-50s | %-7s | %s\n", $label, $breaks, $out );
}
