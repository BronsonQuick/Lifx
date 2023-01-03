<?php
namespace Lifx\Options_Page;

use WP_HTTP_Response;
use function Lifx\Auth\check_token;
use function Lifx\List_Lights\list_lights;
use function Lifx\State\get_colours;

/**
 * Register all our functions for CMB2.
 *
 * @return void
 */
function bootstrap() {
	add_action( 'cmb2_admin_init', __NAMESPACE__ . '\\register_metabox' );
	add_action( 'cmb2_admin_init', __NAMESPACE__ . '\\light_tabs' );
}

/**
 * Add our Lifx options page using CMB2.
 */
function register_metabox() {
	$cmb = new_cmb2_box( [
		'id'           => 'lifx_options_page',
		'title'        => esc_html__( 'LIFX', 'lifx' ),
		'object_types' => [ 'options-page' ],
		'option_key'   => 'lifx_options',
		'tab_group'    => 'lifx_options',
		'tab_title'    => __( 'Settings', 'lifx' ),
		'icon_url'     => 'dashicons-lightbulb',
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

function light_tabs() {
	// Check the authentication.
	$auth = check_token();

	// Bail if we aren't able to authenticate.
	if ( ! $auth instanceof WP_HTTP_Response ) {
		return;
	}

	// Get all the lights.
	$lights = list_lights();

	// Loop over the lights and add a tab for each one.
	if ( ! empty( $lights ) ) {
		foreach ( $lights as $light ) {
			$sanitised_label = strtolower( sanitize_title( $light['label'] ) );
			$args = [
				'id'           => "lifx_${sanitised_label}_options",
				'title'        => esc_html__( "${light['label']} Settings", 'lifx' ),
				'object_types' => [ 'options-page' ],
				'option_key'   => "lifx_${sanitised_label}_options",
				'tab_group'    => 'lifx_options',
				'parent_slug'  => 'lifx_options',
				'tab_title'    => $light['label'],
			];
			$lights_options = new_cmb2_box( $args );

			$lights_options->add_field( [
				'name'     => "${light['label']} Colour",
				'id'       => "${sanitised_label}_color",
				'type'     => 'colorpicker',
				'default'  => '#663399',
				'attributes' => [
					'data-colorpicker' => json_encode( [
						'width'    => 500,
						'alpha'    => true,
						'palettes' => false,
					] ),
				]
			] );
			// Store the device id
			$lights_options->add_field( [
				'id'   => $light['id'],
				'type' => 'hidden',
				]
			);
		}
	}


}