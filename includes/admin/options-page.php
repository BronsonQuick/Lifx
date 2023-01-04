<?php
namespace Lifx\Options_Page;

use WP_HTTP_Response;
use function Lifx\Auth\check_token;
use function Lifx\List_Lights\list_lights;
use function Lifx\List_Lights\zones;
use function Lifx\Power\power;
use function Lifx\State\brightness;
use function Lifx\State\colour;
use Mexitek\PHPColors\Color;

/**
 * Register all our functions for CMB2.
 *
 * @return void
 */
function bootstrap() {
	add_action( 'cmb2_admin_init', __NAMESPACE__ . '\\register_metabox' );
	add_action( 'cmb2_admin_init', __NAMESPACE__ . '\\light_tabs' );
	add_action( 'cmb2_save_field', __NAMESPACE__ . '\\maybe_update_light', 10, 4  );
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

/**
 * Generate a tab for each LIFX light that's on the network.
 *
 * @return void
 */
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
		// Add a controller for all the lights.
		$args = [
			'id'           => "lifx_all_options",
			'title'        => esc_html__( "All Lights Settings", 'lifx' ),
			'object_types' => [ 'options-page' ],
			'option_key'   => 'lifx_all_lights_options',
			'tab_group'    => 'lifx_options',
			'parent_slug'  => 'lifx_options',
			'tab_title'    => __( 'All Lights', 'lifx' ),
		];

		$all_lights_options = new_cmb2_box( $args );

		// Add a colour picker.
		$all_lights_options->add_field( [
			'name'     => __( 'Colour', 'lifx' ),
			'id'       => 'all_lights_lifx_colour',
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

		// Add a power field.
		$all_lights_options->add_field( [
			'name'    => __( 'Power', 'lifx' ),
			'id'      => 'all_lights_lifx_power',
			'type'    => 'radio_inline',
			'options' => [
				'on'  => __( 'On', 'lifx' ),
				'off' => __( 'Off', 'lifx' ),
			],
			'default' => 'on',
		] );

		// Add a field for the brightness.
		$all_lights_options->add_field( [
			'name' => __( 'Brightness', 'lifx' ),
			'id'   => 'all_lights_lifx_brightness',
			'type' => 'text',
			'attributes' => [
				'type'     => 'range',
				'min'      => '0',
				'max'      => '100',
				'onchange' => "document.getElementById( 'brightness' ).value = this.value + '%';",
			],
			'after' => function( $args, $field ) {
				$value = $field->value ? absint( $field->value ) : 50;
				printf(
					"<input type='text' disabled readonly style='background: #fff; color: #32373c; width: 4em;' id='brightness' value=%s />",
					absint( $value ) . '%',
				);
			},
		] );

		// Store the device id as a hidden field.
		$all_lights_options->add_field( [
			'id'      => 'id',
			'default' => 'all',
			'type'    => 'hidden',
		]);

		// Add controls for each light.
		foreach ( $lights as $light ) {
			// A lights name might have special characters so let's strip those out.
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

			// Add a colour picker.
			$lights_options->add_field( [
				'name'     => "${light['label']} Colour",
				'id'       => "${sanitised_label}_lifx_colour",
				'type'     => 'colorpicker',
				'default'  => '#663399',
				'attributes' => [
					'data-colorpicker' => json_encode( [
						'width'    => 500,
						'alpha'    => true,
						'palettes' => false,
					] ),
				],
				'escape_cb' => __NAMESPACE__ . '\\hue_to_hex'
			] );

			// Add a power field.
			$lights_options->add_field( [
				'name'    => __( 'Power', 'lifx' ),
				'id'      => 'lifx_power',
				'type'    => 'radio_inline',
				'options' => [
					'on'  => __( 'On', 'lifx' ),
					'off' => __( 'Off', 'lifx' ),
				],
				'default' => 'on',
			] );

			// Add a field for the brightness.
			$lights_options->add_field( [
				'name' => __( 'Brightness', 'lifx' ),
				'id'   => "${sanitised_label}_lifx_brightness",
				'type' => 'text',
				'attributes' => [
					'type'     => 'range',
					'min'      => '0',
					'max'      => '100',
					'onchange' => "document.getElementById( 'brightness' ).value = this.value + '%';",
				],
				'after' => function( $args, $field ) {
					$value = $field->value ? absint( $field->value ) : 50;
					printf(
						"<input type='text' disabled readonly style='background: #fff; color: #32373c; width: 4em;' id='brightness' value=%s />",
						absint( $value ) . '%',
					);
				},
			] );

			$multizone = zones( "id:${light['id']}" );

			if ( ! empty( $multizone ) ) {
				$lights_options->add_field( array(
					'name'    => __( 'Multizones', 'lifx' ),
					'default' => $multizone[0]['zones'],
					'id'      => "${sanitised_label}_multizone",
					'type'    => 'text_small',
					'attributes' => [
						'readonly' => 'readonly',
					]
				) );
			}

			// Store the device id as a hidden field.
			$lights_options->add_field( [
				'id'      => 'id',
				'default' => $light['id'],
				'type'    => 'hidden',
			]);

			// Add the ability to add more fields to a light.
			$lights_options = apply_filters( 'lifx_light_options', $lights_options );
		}
	}
}

/**
 *
 * Maybe update the light.
 *
 * @param string            $field_id The current field id parameter.
 * @param bool              $updated  Whether the metadata update action occurred.
 * @param string            $action   Action performed. Could be "repeatable", "updated", or "removed".
 * @param CMB2_Field object $field    This field object
 */
function maybe_update_light( $field_id, $updated, $action, $field ) {
	// Don't do anything if the value hasn't updated.
	if ( false === $updated ) {
		return;
	}

	// Add an action to other people can do things when a field is updated.
	do_action( 'lifx_update', $field_id, $updated, $action, $field );

	// Set the selector.
	if ( 'all' ===  $field->data_to_save['id'] ) {
		$selector = 'all';
	} else {
		$selector = 'id:' . $field->data_to_save['id'];
	}

	if ( 'all' ===  $field->data_to_save['id'] ) {
		$power = $field->data_to_save['all_lights_lifx_power'];
	} else {
		$power  = $field->data_to_save['lifx_power'];
	}

	// Check to see if the option contains `_lifx_colour` and if it is then update the light.
	if ( false !== strpos( $field_id, '_lifx_colour' ) ) {
		$colour = $field->value;
		// Set the lights colour.
		colour( $colour, $power, false, $selector );
	}

	// If the power has changed then update it.
	if ( false !== strpos( $field_id, '_lifx_power' ) ) {
		// Change the power of the light.
		power( $power, false, $selector );
	}

	// If the brightness has changed then update it.
	if ( false !== strpos( $field_id, '_lifx_brightness' ) ) {
		$brightness = ( $field->value / 100 );
		brightness( $brightness, false, $selector );
	}
}

/**
 * A function to roughly calculate a hex value from the hue that's returned from the LIFX API.
 *
 * @param $value
 * @param $field_args
 * @param $field
 *
 * @return string
 * @throws \Exception
 */
function hue_to_hex( $value, $field_args, $field ) {

	$label = str_replace( ' Colour', '', $field_args['name'] );

	// Get lights current settings.
	$details         = list_lights( "label:$label" );
	$current_color   = Color::hslToHex( [
		'H' => $details[0]['color']['hue'],
		'S' => 1,
		'L' => 0.5,
		]
	);

	return $current_color;
}