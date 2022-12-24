<?php
namespace Lifx\Options_Page;

/**
 * Register all our functions for CMB2.
 *
 * @return void
 */
function bootstrap() {
	add_action( 'cmb2_admin_init', __NAMESPACE__ . '\\register_metabox' );
}

/**
 * Add our Lifx options page using CMB2.
 */
function register_metabox() {
	$cmb = new_cmb2_box( [
		'id'           => 'lifx_options',
		'title'        => esc_html__( 'LIFX', 'lifx' ),
		'object_types' => [ 'options-page' ],
		'option_key'   => 'lifx_options',
		'parent_slug'  => 'tools.php'
	] );

	// Set our CMB2 fields

	$cmb->add_field( [
		'name' => __( 'Personal Access Token', 'lifx' ),
		'desc' => __( 'LIFX Cloud Personal Access token. <a href="https://cloud.lifx.com/settings">Generate a token</a>', 'lifx' ),
		'id'   => 'lifx_token',
		'type' => 'text',
		'attributes' => [
			'type' => 'password',
		],
	] );

}

/**
 * Wrapper function around cmb2_get_option
 * @since  0.1.0
 * @param  string $key     Options array key
 * @param  mixed  $default Optional default value
 * @return mixed           Option value
 */
function get_option( $key = '', $default = false ) {
	if ( function_exists( 'cmb2_get_option' ) ) {
		// Use cmb2_get_option as it passes through some key filters.
		return cmb2_get_option( 'lifx_options', $key, $default );
	}

	// Fallback to get_option if CMB2 is not loaded yet.
	$opts = get_option( 'lifx_options', $default );

	$val = $default;

	if ( 'all' == $key ) {
		$val = $opts;
	} elseif ( is_array( $opts ) && array_key_exists( $key, $opts ) && false !== $opts[ $key ] ) {
		$val = $opts[ $key ];
	}

	return $val;
}