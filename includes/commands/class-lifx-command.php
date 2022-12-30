<?php
use Lifx\Auth;
use function Lifx\Effects\breathe;
use function Lifx\Effects\effects;
use function Lifx\Effects\flame;
use function Lifx\Effects\move;
use function Lifx\Effects\pulse;
use function Lifx\List_Lights\list_lights;
use function Lifx\Power\power;
use function Lifx\Power\toggle_lights;
use function Lifx\Scenes\activate_scene;
use function Lifx\Scenes\list_scenes;
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
		/**
		 * @param string $selector (Optional) Selector used to filter lights. Defaults to `all`.
		 */
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

		/**
		 * @param string  $selector (Optional) Selector used to filter lights. Defaults to `all`.
		 */
		$response = list_lights( $selector );
		WP_CLI\Utils\format_items( 'table', $response, [ 'id', 'label', 'power', 'brightness', 'connected' ] );
	}

	/**
	 * Lists your Lifx scenes.
	 * https://api.developer.lifx.com/reference/list-scenes
	 *
	 * ## EXAMPLES
	 *
	 * wp lifx scenes
	 *
	 * @when after_wp_load
	 */
	public function list_scenes() {

		$response = list_scenes();
		if ( ! empty( $response ) ) {
		WP_CLI\Utils\format_items( 'table', $response, [ 'name', 'uuid', 'updated_at', 'created_at' ] );
		} else {
			WP_CLI::success( 'No scenes found.' );
		}
	}

	/**
	 * Activate a scene on your Lifx lights.
	 *
	 * ## OPTIONS
	 *
	 * <scene>
	 * : The scene UUID.
	 *
	 * [--fast=<bool>]
	 * : Whether or not to return a response from the LIFX API.
	 *
	 * ## EXAMPLES
	 *
	 * wp lifx activate_scene 6df20b49-5631-4e92-9d9e-6129704dc9fc
	 *
	 * @when after_wp_load
	 */
	public function activate_scene( $args, $assoc_args ) {
		if ( ! empty( $args ) ) {
			list( $scene ) = $args;
		} else {
			WP_CLI::error( 'Please pass in a scene UUID. You can get this by running `wp lifx list_scenes`' );
		}

		if ( ! empty( $assoc_args['fast'] ) ) {
			$fast = $assoc_args['fast'];
		} else {
			$fast = false;
		}

		/**
		 * @param string $scene The UUID of the scene.
		 */
		$response = activate_scene( $scene, $fast );

		if ( is_wp_error( $response ) ) {
			return WP_CLI::error( $response->get_error_message() );
		}

		if ( 207 !== wp_remote_retrieve_response_code( $response ) && 202 !== wp_remote_retrieve_response_code( $response ) ) {
			return WP_CLI::error( $response->get_error_message() );
		}

		// The response will be a 207 if we haven't passed through the fast option.
		if ( 207 === wp_remote_retrieve_response_code( $response ) ) {
			$payload = json_decode( wp_remote_retrieve_body( $response ), true );
			foreach ( $payload['results'] as $light ) {
				WP_CLI::success( "The scene has now been activated on {$light['label']}." );
			}
		}

		// We've passed in fast so we don't get a response payload from the API.
		if ( 202 === wp_remote_retrieve_response_code( $response ) ) {
			WP_CLI::success( "The scene has been activated." );
		}
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
	 * [--duration=<seconds>]
	 * : The time in seconds to apply the change of set over.
	 *
	 * ## EXAMPLES
	 *
	 * wp lifx power on
	 * wp lifx power off
	 * wp lifx power on --selector=label:"I Love Lamp"
	 * wp lifx power on --selector=label:'I Love Lamp' --fast=true
	 * wp lifx power on --selector=label:'I Love Lamp' --fast=true --duration=5
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
			$selector = $assoc_args['selector'];
		} else {
			$selector = 'all';
		}

		if ( ! empty( $assoc_args['duration'] ) ) {
			$duration = $assoc_args['duration'];
		} else {
			$duration = 1;
		}

		/**
		 *
		 * @param string  $state    (Optional) The state of the power. Defaults to `on`.
		 * @param boolean $fast     (Optional) Whether the lights should return a payload or just a status code. Defaults to `false`.
		 * @param string  $selector (Optional) Selector used to filter lights. Defaults to `all`.
		 * @param integer $duration (Optional) The time in seconds to apply the change of set over.
		 *
		 * @return array[]|mixed|\WP_Error
		 */
		$response = power( $power, $fast, $selector, $duration );

		if ( is_wp_error( $response ) ) {
			return WP_CLI::error( $response->get_error_message() );
		}

		if ( 207 !== wp_remote_retrieve_response_code( $response ) && 202 !== wp_remote_retrieve_response_code( $response ) ) {
			return WP_CLI::error( $response->get_error_message() );
		}

		// The response will be a 207 if we haven't passed through the fast option.
		if ( 207 === wp_remote_retrieve_response_code( $response ) ) {
			$payload = json_decode( wp_remote_retrieve_body( $response ), true );
			foreach ( $payload['results'] as $light ) {
				WP_CLI::success( "{$light['label']} is now set to $power." );
			}
		}

		// We've passed in fast so we don't get a response payload from the API.
		if ( 202 === wp_remote_retrieve_response_code( $response ) ) {
			if ( 'all' === $selector ) {
				$status = 'All lights are';
			} else {
				$status = "$selector is";
			}
			WP_CLI::success( "$status now set to $power." );
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
	 * [--duration=<seconds>]
	 * : The time in seconds to apply the change of set over.
	 *
	 * ## EXAMPLES
	 *
	 * wp lifx colour rebeccapurple
	 * wp lifx colour rebeccapurple --fast=true
	 * wp lifx colour rebeccapurple --selector=label:"I Love Lamp" --fast=true
	 * wp lifx colour rebeccapurple --duration=5
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

		if ( ! empty( $assoc_args['duration'] ) ) {
			$duration = $assoc_args['duration'];
		} else {
			$duration = 1;
		}

		/**
		 * @param string  $colour The colour to set the light to. This takes a few formats. i.e. rebeccapurple, random, "#336699", "hue:120 saturation:1.0 brightness:0.5"
		 * Full docs are here: https://api.developer.lifx.com/docs/colors
		 * @param boolean $fast    (Optional) Whether the lights should return a payload or just a status code. Defaults to `false`.
		 * @param string  $selector (Optional) Selector used to filter lights. Defaults to `all`.
		 * @param integer $duration (Optional) The time in seconds to apply the change of set over.
		 */
		$response = colour( $colour, $fast, $selector, $duration );

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
	 * [--duration=<seconds>]
	 * : The time in seconds to apply the change of set over.
	 *
	 * ## EXAMPLES
	 *
	 * wp lifx brightness 0.5
	 * wp lifx brightness 1.0 --fast=true
	 * wp lifx brightness 1.0 --duration=5
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

		if ( ! empty( $assoc_args['duration'] ) ) {
			$duration = $assoc_args['duration'];
		} else {
			$duration = 1;
		}

		/**
		 * @param float   $brightness The brightness level from 0.0 to 1.0. Overrides any brightness set in color (if any).
		 * @param boolean $fast       (Optional) Whether the lights should return a payload or just a status code. Defaults to `false`.
		 * @param string  $selector   (Optional) Selector used to filter lights. Defaults to `all`.
		 * @param integer $duration (Optional) The time in seconds to apply the change of set over.
		 */
		$response = brightness( (float) $brightness, $fast, $selector, $duration );

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
	 * Performs a breathe effect by slowly fading between the given colors. Use the parameters to tweak the effect.
	 * https://api.developer.lifx.com/reference/breathe-effect
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
	 * wp lifx breathe deeppink --from_colour=darkblue --cycles=3
	 * wp lifx breathe deeppink --from_colour=darkblue --cycles=3 --period=5
	 * wp lifx breathe deeppink --from_colour=rebeccapurple --cycles=3 --period=5 --power_on=false
	 * wp lifx breathe deeppink --from_colour=rebeccapurple --cycles=3 --period=5 --power_on=false --persist=true
	 * wp lifx breathe deeppink --from_colour=rebeccapurple --cycles=3 --period=5 --power_on=false --persist=true --peak=1
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
			$power_on = false;
		} else {
			$power_on = true;
		}

		if ( ! empty( $assoc_args['peak'] ) ) {
			$peak = $assoc_args['peak'];
		} else {
			$peak = 0.5;
		}

		/**
		 * @param string  $colour      The colour to set the light to. This takes a few formats. i.e. rebeccapurple, random, '#336699', 'hue:120 saturation:1.0 brightness:0.5'
		 * @param string  $from_colour (Optional) The colour to start the effect from. This takes a few formats. i.e. rebeccapurple, random, '#336699', 'hue:120 saturation:1.0 brightness:0.5'
		 * @param string  $selector    (Optional) Selector used to filter lights. Defaults to `all`.
		 * @param int     $period      (Optional) The time in seconds for one cycle of the effect.
		 * @param int     $cycles      (Optional) The number of times to repeat the effect.
		 * @param boolean $persist     (Optional) If false set the light back to its previous value of 'from_color' when effect ends, if true leave the last effect color.
		 * @param boolean $power_on    (Optional) If true, turn the bulb on if it is not already on.
		 * @param float   $peak        (Optional) Defines where in a period the target color is at its maximum. Minimum 0.0, maximum 1.0.
		 *
		 */
		$response = breathe( $colour, $from_colour, $selector, $period, $cycles, $persist, $power_on, $peak );

		// The response should be a 207 Multi-Status.
		if ( 207 !== wp_remote_retrieve_response_code( $response ) ) {
			return WP_CLI::error( $response->get_error_message() );
		}

		// The response will be a 207.
		if ( 207 === wp_remote_retrieve_response_code( $response ) ) {
			$payload = json_decode( wp_remote_retrieve_body( $response ), true );
			foreach ( $payload['results'] as $light ) {
				WP_CLI::success( "{$light['label']} is completing the breathe effect." );
			}
		}
	}

	/**
	 * Performs a pulse effect by quickly flashing between the given colors. Use the parameters to tweak the effect.
	 * https://api.developer.lifx.com/reference/pulse-effect
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
	 * ## EXAMPLES
	 *
	 * wp lifx pulse rebeccapurple
	 * wp lifx pulse deeppink --from_colour=darkblue --cycles=3
	 * wp lifx pulse deeppink --from_colour=darkblue --cycles=3 --period=5
	 * wp lifx pulse deeppink --from_colour=rebeccapurple --cycles=3 --period=5 --power_on=false
	 * wp lifx pulse deeppink --from_colour=rebeccapurple --cycles=3 --period=5 --power_on=false --persist=true
	 *
	 * @when after_wp_load
	 */
	public function pulse( $args, $assoc_args ) {
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

		/**
		 * @param string  $colour      The colour to set the light to. This takes a few formats. i.e. rebeccapurple, random, '#336699', 'hue:120 saturation:1.0 brightness:0.5'
		 * @param string  $from_colour (Optional) The colour to start the effect from. This takes a few formats. i.e. rebeccapurple, random, '#336699', 'hue:120 saturation:1.0 brightness:0.5'
		 * @param string  $selector    (Optional) Selector used to filter lights. Defaults to `all`.
		 * @param int     $period      (Optional) The time in seconds for one cycle of the effect.
		 * @param int     $cycles      (Optional) The number of times to repeat the effect.
		 * @param boolean $persist     (Optional) If false set the light back to its previous value of 'from_color' when effect ends, if true leave the last effect color.
		 * @param boolean $power_on    (Optional) If true, turn the bulb on if it is not already on.
		 *
		 */

		$response = pulse( $colour, $from_colour, $selector, $period, $cycles, $persist, $power_on );

		// The response should be a 207 Multi-Status.
		if ( 207 !== wp_remote_retrieve_response_code( $response ) ) {
			return WP_CLI::error( $response->get_error_message() );
		}

		// The response will be a 207.
		if ( 207 === wp_remote_retrieve_response_code( $response ) ) {
			$payload = json_decode( wp_remote_retrieve_body( $response ), true );
			foreach ( $payload['results'] as $light ) {
				WP_CLI::success( "{$light['label']} is completing the pulse effect." );
			}
		}
	}

	/**
	 * Performs a flame effect on the tiles in your selector. Use the parameters to tweak the effect.
	 * https://api.developer.lifx.com/reference/flame-effect
	 *
	 * ## OPTIONS
	 *
	 * [--selector=<type>]
	 * : The selector you wish to use. i.e. label, id, group_id, location, location_id
	 *
	 * [--period=<seconds>]
	 * : The time in seconds for one cycle of the effect.
	 *
	 * [--duration=<seconds>]
	 * : The time in seconds for one cycle of the effect.
	 *
	 * [--power_on=<boolean>]
	 * : If true, turn the bulb on if it is not already on.
	 *
	 * [--fast=<bool>]
	 * : Whether or not to return a response from the LIFX API.
	 *
	 * ## EXAMPLES
	 *
	 * wp lifx flame rebeccapurple
	 *
	 * @when after_wp_load
	 */
	public function flame( $args, $assoc_args ) {

		if ( ! empty( $assoc_args['selector'] ) ) {
			$selector = $assoc_args['selector'];
		} else {
			$selector = 'all';
		}

		if ( ! empty( $assoc_args['period'] ) ) {
			$period = $assoc_args['period'];
		} else {
			$period = 1;
		}

		if ( ! empty( $assoc_args['duration'] ) ) {
			$duration = $assoc_args['duration'];
		} else {
			$duration = 1;
		}

		if ( ! empty( $assoc_args['power_on'] ) ) {
			$power_on = $assoc_args['power_on'];
		} else {
			$power_on = true;
		}

		if ( ! empty( $assoc_args['fast'] ) ) {
			$fast = $assoc_args['fast'];
		} else {
			$fast = false;
		}

		/**
		 *
		 * @param string  $selector    (Optional) Selector used to filter lights. Defaults to `all`.
		 * @param int     $period      (Optional) This controls how quickly the flame runs. It is measured in seconds. A lower number means the animation is faster.
		 * @param int     $duration    (Optional) How long the animation lasts for in seconds. Defaults to `1`.
		 * @param boolean $power_on    (Optional) If true, turn the bulb on if it is not already on.
		 * @param boolean $fast        (Optional) Whether the lights should return a payload or just a status code. Defaults to `false`.
		 *
		 * @return array[]|mixed|\WP_Error
		 */

		$response = flame( $selector, $period, $duration, $power_on, $fast );

				if ( 207 !== wp_remote_retrieve_response_code( $response ) && 202 !== wp_remote_retrieve_response_code( $response ) ) {
			return WP_CLI::error( $response->get_error_message() );
		}

		// The response will be a 207 if we haven't passed through the fast option.
		if ( 207 === wp_remote_retrieve_response_code( $response ) ) {
			$payload = json_decode( wp_remote_retrieve_body( $response ), true );
			foreach ( $payload['results'] as $light ) {
				WP_CLI::success( "{$light['label']} is now completing a flame effect." );
			}
		}

		// We've passed in fast so we don't get a response payload from the API.
		if ( 202 === wp_remote_retrieve_response_code( $response ) ) {
			if ( 'all' === $selector ) {
				$status = 'All lights are';
			} else {
				$status = "$selector is";
			}
			WP_CLI::success( "$status now completing a flame effect." );
		}
	}

	/**
	 * Performs a move effect by quickly flashing between the given colors. Use the parameters to tweak the effect.
	 * https://api.developer.lifx.com/reference/move-effect
	 *
	 * ## OPTIONS
	 *
	 * <direction>
	 * : Move direction, can be forward or backward.
	 *
	 * [--selector=<type>]
	 * : The selector you wish to use. i.e. label, id, group_id, location, location_id
	 *
	 * [--fast=<bool>]
	 * : Whether or not to return a response from the LIFX API.
	 *
	 * [--period=<seconds>]
	 * : The time in seconds for one cycle of the effect.
	 *
	 * [--cycles=<number>]
	 * : The time in seconds for one cycle of the effect.
	 *
	 * [--power_on=<boolean>]
	 * : If true, turn the bulb on if it is not already on.
	 *
	 * ## EXAMPLES
	 *
	 * wp lifx move forward
	 * wp lifx move backward --selector=group:"Music Room"
	 * wp lifx move backward --selector=group:"Music Room" --fast=true
	 * wp lifx move forward --cycles=3 --period=5
	 * wp lifx move backward --cycles=10 --period=10 --power_on=true
	 *
	 * @when after_wp_load
	 */
	public function move( $args, $assoc_args ) {
		if ( ! empty( $args ) ) {
			list( $direction ) = $args;
		} else {
			WP_CLI::error( 'Please pass in a direction.' );
		}

		if ( ! empty( $assoc_args['selector'] ) ) {
			$selector = $assoc_args['selector'];
		} else {
			$selector = 'all';
		}

		if ( ! empty( $assoc_args['fast'] ) ) {
			$fast = true;
		} else {
			$fast = false;
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

		if ( ! empty( $assoc_args['power_on'] ) ) {
			$power_on = false;
		} else {
			$power_on = true;
		}

		/**
		 * @param string  $direction   (Optional) Move direction, can be forward or backward.
		 * @param string  $selector    (Optional) Selector used to filter lights. Defaults to `all`.
		 * @param boolean $fast        (Optional) Whether the lights should return a payload or just a status code. Defaults to `false`.
		 * @param int     $period      (Optional) The time in seconds for one cycle of the effect.
		 * @param int     $cycles      (Optional) The number of times to repeat the effect.
		 * @param boolean $power_on    (Optional) If true, turn the bulb on if it is not already on.
		 */
		$response = move( $direction, $selector, $fast, $period, $cycles, $power_on );

		if ( 207 !== wp_remote_retrieve_response_code( $response ) && 202 !== wp_remote_retrieve_response_code( $response ) ) {
			return WP_CLI::error( $response->get_error_message() );
		}

		// The response will be a 207 if we haven't passed through the fast option.
		if ( 207 === wp_remote_retrieve_response_code( $response ) ) {
			$payload = json_decode( wp_remote_retrieve_body( $response ), true );
			foreach ( $payload['results'] as $light ) {
				WP_CLI::success( "{$light['label']} is now completing a move effect." );
			}
		}

		// We've passed in fast so we don't get a response payload from the API.
		if ( 202 === wp_remote_retrieve_response_code( $response ) ) {
			if ( 'all' === $selector ) {
				$status = 'All lights are';
			} else {
				$status = "$selector is";
			}
			WP_CLI::success( "$status now completing a move effect." );
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

	/**
	 * Disable any effects running on one or all lights.
	 *
	 * ## EXAMPLES
	 *
	 * wp lifx effects
	 * wp lifx effects --power_off=true
	 * wp lifx effects --selector=label:"I Love Lamp"
	 * wp lifx effects --selector=label:'I Love Lamp' --power_off=true
	 *
	 * @when after_wp_load
	 */
	public function effects( $args, $assoc_args ) {
		if ( ! empty( $assoc_args['selector'] ) ) {
			$selector = $assoc_args['selector'];
		} else {
			$selector = 'all';
		}

		if ( ! empty( $assoc_args['power_off'] ) ) {
			$power_off = true;
		} else {
			$power_off = false;
		}

		/**
		 * @param string  $selector  (Optional) Selector used to filter lights. Defaults to `all`.
		 * @param boolean $power_off (Optional) Whether to turn off the light(s) as well. Defaults to `false`.
		 */
		$response = effects( $selector, $power_off );
			// The response should be a 207 Multi-Status.
		if ( 207 !== wp_remote_retrieve_response_code( $response ) ) {
			return WP_CLI::error( $response->get_error_message() );
		}

		// The response will be a 207.
		if ( 207 === wp_remote_retrieve_response_code( $response ) ) {
			$payload = json_decode( wp_remote_retrieve_body( $response ), true );
			foreach ( $payload['results'] as $light ) {
				WP_CLI::success( "Effects on {$light['label']} have been cancelled." );
			}
		}

	}
}

WP_CLI::add_command( 'lifx', 'Lifx_Command' );