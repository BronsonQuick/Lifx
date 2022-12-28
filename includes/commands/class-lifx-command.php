<?php
use Lifx\Auth;
use function Lifx\Effects\breathe;
use function Lifx\List_Lights\list_lights;
use function Lifx\Power\power;
use function Lifx\Power\toggle_lights;
use function Lifx\State\brightness;
use function Lifx\State\colour;
use function Lifx\State\get_colours;
use function Lifx\State\validate_colour;

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
		$response = toggle_lights( $selector );
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
		$response = list_lights( $selector );
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
	 * [--fast=<bool>]
	 * : Whether or not to return a response from the LIFX API.
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
		if ( ! empty( $assoc_args['fast'] ) ) {
			$fast = $assoc_args['fast'];
		} else {
			$fast = false;
		}
		if ( ! empty( $assoc_args['selector'] ) ) {
			$response = power( $power, $fast, $assoc_args['selector'] );
		} else {
			$response = power( $power, $fast );
		}

		if ( is_wp_error( $response ) ) {
			return WP_CLI::error( $response->get_error_message() );
		}

		// We don't get results when we set fast to true so we need to check the http response code.
		if ( ! empty( $assoc_args['fast'] ) ) {
			if ( 202 === wp_remote_retrieve_response_code( $response ) ) {
				if ( empty( $light ) ) {
					$status = 'All lights are';
				} else {
					$status = "$light is";
				}
				WP_CLI::success( "$status now $power." );
			} else {
				WP_CLI::error( 'Something went wrong' );
			}
		} else {
			$payload = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! empty( $payload ) ) {
				foreach ( $payload['results'] as $light ) {
					WP_CLI::success( "{$light['label']} is now $power." );
				}
			} else {
				WP_CLI::error( 'Something went wrong' );
			}
		}
	}

	/**
	 * Checks to see if the string the user has entered is a valid colour.
	 * https://api.developer.lifx.com/docs/colors
	 *
	 * ## OPTIONS
	 *
	 * <colour>
	 * : The state of the light. i.e. on or off
	 *
	 *
	 * ## EXAMPLES
	 *
	 * wp lifx validate_colour red
	 * wp lifx validate_colour "#663399"
	 * wp lifx validate_colour "hue:120 saturation:1.0 brightness:0.5"
	 * wp lifx validate_colour "kelvin:2700 brightness: 0.5"
	 * wp lifx validate_colour "rgb:0,255,255"
	 * wp lifx validate_colour "kelvin:5000"
	 * wp lifx validate_colour "kelvin:2700 saturation:1"
	 * wp lifx validate_colour "saturation:0.25"
	 *
	 * @when after_wp_load
	 */
	public function validate_colour( $args ) {
		if ( ! empty( $args ) ) {
			list( $colour ) = $args;
			$colour = strtolower( $colour );
		} else {
			WP_CLI::error( 'Please pass in a colour string, hex value, or string.' );
		}

		$colours = get_colours();

		// If the colour is in our list of colours then return the Hex value.
		if ( true === array_key_exists( $colour, $colours ) ) {
			return WP_CLI::success( "$colour is a successful colour and the hex value is $colours[$colour]." );
		}

		/**
		 * If the colour name or hex values doesn't exist then we should validate it via the LIFX API.
		 * https://api.developer.lifx.com/reference/validate-color
		 */
		if ( false === in_array( $colour, $colours, true ) ) {
			$validation = validate_colour( $colour );
			if ( is_wp_error( $validation ) ) {
				return $validation;
			}
		}

		WP_CLI::success( "$colour is a successful colour." );
	}

	/**
	 * Sets the colour for all lights or a specific light.
	 * https://api.developer.lifx.com/docs/colors
	 *
	 * ## OPTIONS
	 *
	 * <colour>
	 * : The colour to set the lights.
	 *
	 * [--selector=<type>]
	 * : The selector you wish to use. i.e. label, id, group_id, location, location_id
	 *
	 * [--fast=<bool>]
	 * : Whether or not to return a response from the LIFX API.
	 *
	 * ## EXAMPLES
	 *
	 * wp lifx colour rebeccapurple
	 * wp lifx colour rebeccapurple --fast=true
	 * wp lifx colour rebeccapurple --selector=label:"I Love Lamp" --fast=true
	 * wp lifx colour random
	 * wp lifx colour "#663399"
	 * wp lifx colour "hue:120 saturation:1.0 brightness:0.5"
	 * wp lifx colour "kelvin:2700 brightness: 0.5"
	 * wp lifx colour "rgb:0,255,255"
	 * wp lifx colour "kelvin:5000"
	 * wp lifx colour "kelvin:2700 saturation:1"
	 * wp lifx colour "saturation:0.25"
	 *
	 * @when after_wp_load
	 */
	public function colour( $args, $assoc_args ) {
		if ( ! empty( $args ) ) {
			list( $colour ) = $args;
			$colour = strtolower( $colour );
		} else {
			WP_CLI::error( 'Please pass in a colour string, hex value, or string.' );
		}

		// If the colour is "random" then let's randomly choose a colour from our built-in colours.
		if ( 'random' === $colour ) {
			$colours = get_colours();
			$colour = array_rand( $colours );
		}

		if ( ! empty( $assoc_args['fast'] ) ) {
			$fast = $assoc_args['fast'];
		} else {
			$fast = false;
		}

		if ( ! empty( $assoc_args['selector'] ) ) {
			$selector = $assoc_args['selector'];
		} else {
			$selector = 'all';
		}

		$response = colour( $colour, $fast, $selector );

		if ( 207 !== wp_remote_retrieve_response_code( $response ) && 202 !== wp_remote_retrieve_response_code( $response ) ) {
			return WP_CLI::error( $response->get_error_message() );
		}

		// The response will be a 207 if we haven't passed through the fast option.
		if ( 207 === wp_remote_retrieve_response_code( $response ) ) {
			$payload = json_decode( wp_remote_retrieve_body( $response ), true );
			foreach ( $payload['results'] as $light ) {
				WP_CLI::success( "{$light['label']} is now set to $colour." );
			}
		}

		// We've passed in fast so we don't get a response payload from the API.
		if ( 202 === wp_remote_retrieve_response_code( $response ) ) {
			if ( 'all' === $selector ) {
				$status = 'All lights are';
			} else {
				$status = "$selector is";
			}
			WP_CLI::success( "$status now set to $colour." );
		}
	}

	/**
	 * Sets the brightness for all lights or a specific light.
	 * https://api.developer.lifx.com/reference/set-state
	 *
	 * ## OPTIONS
	 *
	 * <brightness>
	 * : The brightness level from 0.0 to 1.0. Overrides any brightness set in color (if any).
	 *
	 * [--selector=<type>]
	 * : The selector you wish to use. i.e. label, id, group_id, location, location_id
	 *
	 * [--fast=<bool>]
	 * : Whether or not to return a response from the LIFX API.
	 *
	 * ## EXAMPLES
	 *
	 * wp lifx brightness 0.5
	 * wp lifx brightness 1.0 --fast=true
	 * wp lifx brightness 0.75 --selector=group:Bedroom
	 * wp lifx brightness 0.75 --selector=label:'I Love Lamp'
	 *
	 * @when after_wp_load
	 */
	public function brightness( $args, $assoc_args ) {
		if ( ! empty( $args ) ) {
			list( $brightness ) = $args;
		} else {
			WP_CLI::error( 'Please pass in a brightness value from between 0.0 to 1.0' );
		}

		if ( ! empty( $assoc_args['fast'] ) ) {
			$fast = $assoc_args['fast'];
		} else {
			$fast = false;
		}

		if ( ! empty( $assoc_args['selector'] ) ) {
			$selector = $assoc_args['selector'];
		} else {
			$selector = 'all';
		}

		$response = brightness( (float) $brightness, $fast, $selector );

		if ( 207 !== wp_remote_retrieve_response_code( $response ) && 202 !== wp_remote_retrieve_response_code( $response ) ) {
			return WP_CLI::error( $response->get_error_message() );
		}

		// The response will be a 207 if we haven't passed through the fast option.
		if ( 207 === wp_remote_retrieve_response_code( $response ) ) {
			$payload = json_decode( wp_remote_retrieve_body( $response ), true );
			foreach ( $payload['results'] as $light ) {
				WP_CLI::success( "{$light['label']} is now set at $brightness brightness." );
			}
		}

		// We've passed in fast so we don't get a response payload from the API.
		if ( 202 === wp_remote_retrieve_response_code( $response ) ) {
			if ( 'all' === $selector ) {
				$status = 'All lights are';
			} else {
				$status = "$selector is";
			}
			WP_CLI::success( "$status now at $brightness brightness." );
		}
	}

	/**
	 * Sets the colour for all lights or a specific light.
	 * https://api.developer.lifx.com/docs/colors
	 *
	 * ## OPTIONS
	 *
	 * <colour>
	 * : The colour to set the light to.
	 *
	 * [--from_colour=<string>]
	 * : The colour to start the effect from.
	 *
	 * [--selector=<type>]
	 * : The selector you wish to use. i.e. label, id, group_id, location, location_id
	 *
	 * [--from_colour=<string>]
	 * : The colour to start the effect from.
	 *
	 * [--period=<seconds>]
	 * : The time in seconds for one cycle of the effect.
	 *
	 * [--cycles=<number>]
	 * : The time in seconds for one cycle of the effect.
	 *
	 * [--persist=<boolean>]
	 * : If false set the light back to its previous value of 'from_color' when effect ends.
	 *
	 * [--power_on=<boolean>]
	 * : If true, turn the bulb on if it is not already on.
	 *
	 * [--peak=<float>]
	 * : Defines where in a period the target color is at its maximum. Minimum 0.0, maximum 1.0.
	 *
	 * ## EXAMPLES
	 *
	 * wp lifx breathe rebeccapurple
	 *
	 * @when after_wp_load
	 */
	public function breathe( $args, $assoc_args ) {
		if ( ! empty( $args ) ) {
			list( $colour ) = $args;
			$colour = strtolower( $colour );
		} else {
			WP_CLI::error( 'Please pass in a colour string, hex value, or string.' );
		}

		if ( ! empty( $assoc_args['selector'] ) ) {
			$selector = $assoc_args['selector'];
		} else {
			$selector = 'all';
		}

		if ( ! empty( $assoc_args['from_colour'] ) ) {
			$from_colour = $assoc_args['from_colour'];
		} else {
			$from_colour = null;
		}

		if ( ! empty( $assoc_args['period'] ) ) {
			$period = $assoc_args['period'];
		} else {
			$period = 1;
		}

		if ( ! empty( $assoc_args['cycles'] ) ) {
			$cycles = $assoc_args['cycles'];
		} else {
			$cycles = 1;
		}

		if ( ! empty( $assoc_args['persist'] ) ) {
			$persist = $assoc_args['persist'];
		} else {
			$persist = false;
		}

		if ( ! empty( $assoc_args['power_on'] ) ) {
			$power_on = $assoc_args['power_on'];
		} else {
			$power_on = true;
		}

		if ( ! empty( $assoc_args['peak'] ) ) {
			$peak = $assoc_args['peak'];
		} else {
			$peak = 0.5;
		}

		$response = breathe( $colour, $from_colour, $selector, $period, $cycles, $persist, $power_on, $peak );

		// The response should be a 207 Multi-Status.
		if ( 207 !== wp_remote_retrieve_response_code( $response ) ) {
			return WP_CLI::error( $response->get_error_message() );
		}

		// The response will be a 207.
		if ( 207 === wp_remote_retrieve_response_code( $response ) ) {
			$payload = json_decode( wp_remote_retrieve_body( $response ), true );
			foreach ( $payload['results'] as $light ) {
				WP_CLI::success( "{$light['label']} has completed the breathe effect." );
			}
		}
	}

	/**
	 * List the colour names you can use with our plugin.
	 *
	 * ## EXAMPLES
	 *
	 * wp lifx colour_list
	 *
	 * @when after_wp_load
	 */
	public function colour_list() {
		$colours = get_colours();
		$list = [];
		foreach ( $colours as $key => $value ) {
			$list[] = [
				'name'  => $key,
				'value' => $value
			];
		}
		WP_CLI\Utils\format_items( 'table', $list, [ 'name', 'value' ] );
	}
}

WP_CLI::add_command( 'lifx', 'Lifx_Command' );