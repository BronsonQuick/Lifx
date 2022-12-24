<?php
use Lifx\Auth;

class Lifx_Command {
	/**
	 * Prints your Lifx personal access token.
	 *
	 * ## EXAMPLES
	 *
	 * wp lifx get_token
	 *
	 * @when after_wp_load
	 */
	public function get_token() {
		$token = Auth\get_token();
		WP_CLI::success( "Your Lifx token is set to: $token" );
	}

	/**
	 * Checks to see if your Lifx Personal Token is correctly authenticating.
	 *
	 * ## EXAMPLES
	 *
	 * wp lifx check_token
	 *
	 * @when after_wp_load
	 */
	public function check_token() {
		$response = Auth\check_token();
		WP_CLI::success( $response );
	}

	/**
	 * Toggles your Lifx light.
	 *
	 * ## OPTIONS
	 *
	 * [--selector=<type>]
	 * : Whether or not to greet the person with success or error.
	 *
	 * ## EXAMPLES
	 *
	 * wp lifx toggle
	 * wp lifx toggle --selector=label:"I Love Lamp"
	 *
	 * @when after_wp_load
	 */
	public function toggle( $args, $assoc_args ) {
		if ( ! empty( $assoc_args['selector'] ) ) {
			$selector = $assoc_args['selector'];
		} else {
			$selector = 'all';
		}
		$response = \Lifx\Power\toggle_lights( $selector );
		if ( ! empty( $response ) ) {
			foreach ( $response['results'] as $light ) {
				WP_CLI::success( "{$light['label']} is now {$light['power']}." );
			}
		} else {
			WP_CLI::error( 'Something went wrong' );
		}
	}

	/**
	 * Lists your Lifx lights.
	 * https://api.developer.lifx.com/reference/set-state
	 *
	 * ## OPTIONS
	 *
	 * [--selector=<type>]
	 * : The selector you wish to use. i.e. label, id, group_id, location, location_id
	 *
	 * ## EXAMPLES
	 *
	 * wp lifx list_lights
	 * wp lifx list_lights --selector=label:'I Love Lamp'
	 *
	 * @when after_wp_load
	 */
	public function list_lights( $args, $assoc_args ) {
		if ( ! empty( $assoc_args['selector'] ) ) {
			$selector = $assoc_args['selector'];
		} else {
			$selector = 'all';
		}
		$response = \Lifx\List_Lights\list_lights( $selector );
		WP_CLI\Utils\format_items( 'table', $response, [ 'id', 'label', 'power', 'brightness', 'connected' ] );
	}

	/**
	 * Sets the power for all lights or a specific light.
	 * https://api.developer.lifx.com/reference/set-state
	 *
	 * ## OPTIONS
	 *
	 * <power>
	 * : The state of the light. i.e. on or off
	 *
	 * [--selector=<type>]
	 * : The selector you wish to use. i.e. label, id, group_id, location, location_id
	 *
	 * ## EXAMPLES
	 *
	 * wp lifx power on
	 * wp lifx power off
	 * wp lifx power on --selector=label:"I Love Lamp"
	 * wp lifx power on --selector=label:'I Love Lamp' --fast=true
	 *
	 * @when after_wp_load
	 */
	public function power( $args, $assoc_args ) {
		if ( ! empty( $args ) ) {
			list( $power ) = $args;
		}
		if ( ! empty( $assoc_args['selector'] ) && ! empty( $assoc_args['fast'] ) ) {
			$response = \Lifx\Power\power( $power, $assoc_args['selector'], $assoc_args['fast'] );
		} elseif ( ! empty( $assoc_args['selector'] ) ) {
			$response = \Lifx\Power\power( $power, $assoc_args['selector'] );
		} else {
			$response = \Lifx\Power\power( $power );
		}

		// We don't get results when we set fast to true so we need to check the http response code.
		if ( ! empty( $assoc_args['fast'] ) ) {
			if ( 202 !== wp_remote_retrieve_response_code( $response ) ) {
				if ( empty( $light ) ) {
					$status = 'All lights are';
				} else {
					$status = "$light is";
				}
				WP_CLI::success( "$status now $power." );
			}
		} else {
			if ( ! empty( $response ) ) {
				foreach ( $response['results'] as $light ) {
					WP_CLI::success( "{$light['label']} is now $power." );
				}
			} else {
				WP_CLI::error( 'Something went wrong' );
			}
		}
	}
}

WP_CLI::add_command( 'lifx', 'Lifx_Command' );