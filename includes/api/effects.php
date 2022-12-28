<?php
namespace Lifx\Effects;

use function Lifx\Auth\get_headers;
use function Lifx\State\validate_web_colours;

/**
 * Performs a breathe effect by slowly fading between the given colors. Use the parameters to tweak the effect.
 *
 * @param string  $colour      The colour to set the light to. This takes a few formats. i.e. rebeccapurple, random, '#336699', 'hue:120 saturation:1.0 brightness:0.5'
 * @param string  $from_colour (Optional) The colour to start the effect from. This takes a few formats. i.e. rebeccapurple, random, '#336699', 'hue:120 saturation:1.0 brightness:0.5'
 * @param string  $selector    (Optional) Selector used to filter lights. Defaults to `all`.
 * @param int     $period      (Optional) The time in seconds for one cycle of the effect.
 * @param int     $cycles      (Optional) The number of times to repeat the effect.
 * @param boolean $persist     (Optional) If false set the light back to its previous value of "from_color" when effect ends, if true leave the last effect color.
 * @param boolean $power_on    (Optional) If true, turn the bulb on if it is not already on.
 * @param float   $peak        (Optional) Defines where in a period the target color is at its maximum. Minimum 0.0, maximum 1.0.
 *
 * @return array[]|mixed|\WP_Error
 */
function breathe( $colour, $from_colour = null, $selector = 'all', $period = 1, $cycles = 1, $persist = false, $power_on = true, $peak = 0.5 ) {
	$headers = get_headers();

	if ( is_wp_error( $headers ) ) {
		return $headers;
	}

	$colour_string      = validate_web_colours( $colour );
	$from_colour_string = validate_web_colours( $from_colour );

	$endpoint = LIFX_ENDPOINT . "/lights/$selector/effects/breathe";

	$persist  = filter_var( $persist, FILTER_VALIDATE_BOOLEAN );
	$power_on = filter_var( $power_on, FILTER_VALIDATE_BOOLEAN );

	$defaults = [
		'method'  => 'POST',
		'timeout' => 10,
		'body'    => [
			'color'      => $colour_string,
			'from_color' => $from_colour_string,
			'period'     => (int) $period,
			'cycles'     => (int) $cycles,
			'persist'    => $persist,
			'power_on'   => $power_on,
			'peak'       => (float) $peak,
		],
	];

	$payload = array_merge( $defaults, $headers );

	$payload['body'] = wp_json_encode( $payload['body'] );

	$request = wp_safe_remote_post(
		$endpoint,
		$payload
	);

	return $request;
}

/**
 * Performs a pulse effect by quickly flashing between the given colors. Use the parameters to tweak the effect.
 *
 * @param string  $colour      The colour to set the light to. This takes a few formats. i.e. rebeccapurple, random, '#336699', 'hue:120 saturation:1.0 brightness:0.5'
 * @param string  $from_colour (Optional) The colour to start the effect from. This takes a few formats. i.e. rebeccapurple, random, '#336699', 'hue:120 saturation:1.0 brightness:0.5'
 * @param string  $selector    (Optional) Selector used to filter lights. Defaults to `all`.
 * @param int     $period      (Optional) The time in seconds for one cycle of the effect.
 * @param int     $cycles      (Optional) The number of times to repeat the effect.
 * @param boolean $persist     (Optional) If false set the light back to its previous value of "from_color" when effect ends, if true leave the last effect color.
 * @param boolean $power_on    (Optional) If true, turn the bulb on if it is not already on.
 *
 * @return array[]|mixed|\WP_Error
 */
function pulse( $colour, $from_colour = null, $selector = 'all', $period = 1, $cycles = 1, $persist = false, $power_on = true ) {
	$headers = get_headers();

	if ( is_wp_error( $headers ) ) {
		return $headers;
	}

	$colour_string      = validate_web_colours( $colour );
	$from_colour_string = validate_web_colours( $from_colour );

	$endpoint = LIFX_ENDPOINT . "/lights/$selector/effects/pulse";

	$persist  = filter_var( $persist, FILTER_VALIDATE_BOOLEAN );
	$power_on = filter_var( $power_on, FILTER_VALIDATE_BOOLEAN );

	$defaults = [
		'method'  => 'POST',
		'timeout' => 10,
		'body'    => [
			'color'      => $colour_string,
			'from_color' => $from_colour_string,
			'period'     => (int) $period,
			'cycles'     => (int) $cycles,
			'persist'    => $persist,
			'power_on'   => $power_on
		],
	];

	$payload = array_merge( $defaults, $headers );

	$payload['body'] = wp_json_encode( $payload['body'] );

	$request = wp_safe_remote_post(
		$endpoint,
		$payload
	);

	return $request;
}

/**
 * Performs a move effect on a linear device with zones, by moving the current pattern across the device. Use the parameters to tweak the effect.
 *
 * @param string  $direction   (Optional) Move direction, can be forward or backward.
 * @param string  $selector    (Optional) Selector used to filter lights. Defaults to `all`.
 * @param boolean $fast        (Optional) Whether the lights should return a payload or just a status code. Defaults to `false`.
 * @param int     $period      (Optional) The time in seconds for one cycle of the effect.
 * @param int     $cycles      (Optional) The number of times to repeat the effect.
 * @param boolean $power_on    (Optional) If true, turn the bulb on if it is not already on.
 *
 * @return array[]|mixed|\WP_Error
 */
function move( $direction = 'forward', $selector = 'all', $fast = false, $period = 1, $cycles = 1, $power_on = true ) {
	$headers = get_headers();

	if ( is_wp_error( $headers ) ) {
		return $headers;
	}

	$endpoint = LIFX_ENDPOINT . "/lights/$selector/effects/move";

	$power_on = filter_var( $power_on, FILTER_VALIDATE_BOOLEAN );

	$defaults = [
		'method'  => 'POST',
		'timeout' => 10,
		'body'    => [
			'direction'  => $direction,
			'fast'       => $fast,
			'period'     => (int) $period,
			'cycles'     => (int) $cycles,
			'power_on'   => $power_on
		],
	];

	$payload = array_merge( $defaults, $headers );

	// Filter our booleans.
	$payload['body']['fast'] = filter_var( $payload['body']['fast'], FILTER_VALIDATE_BOOLEAN );
	$payload['body']['power_on'] = filter_var( $payload['body']['power_on'], FILTER_VALIDATE_BOOLEAN );

	$payload['body'] = wp_json_encode( $payload['body'] );

	$request = wp_safe_remote_post(
		$endpoint,
		$payload
	);

	return $request;
}

/**
 * A function to stop any effects running on all lights or a specific light.
 *
 * @param string  $selector  (Optional) Selector used to filter lights. Defaults to `all`.
 * @param boolean $power_off (Optional) Whether to turn off the light(s) as well. Defaults to `false`.
 *
 * @return array|array[]|\WP_Error
 */
function effects( $selector = 'all', $power_off = false ) {
	$headers = get_headers();

	if ( is_wp_error( $headers ) ) {
		return $headers;
	}

	$endpoint = LIFX_ENDPOINT . "/lights/$selector/effects/off";

	$power_off = filter_var( $power_off, FILTER_VALIDATE_BOOLEAN );

	$defaults = [
		'method'  => 'POST',
		'timeout' => 10,
		'body'    => [
			'power_off' => $power_off,
		],
	];

	$payload = array_merge( $defaults, $headers );

	$payload['body'] = wp_json_encode( $payload['body'] );

	$request = wp_safe_remote_post(
		$endpoint,
		$payload
	);

	return $request;
}