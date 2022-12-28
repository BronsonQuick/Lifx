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

	$defaults = [
		'method' => 'POST',
		'body'   => [
			'color'      => $colour_string,
			'from_color' => $from_colour_string,
			'period'     => (int) $period,
			'cycles'     => (int) $cycles,
			'persist'    => (bool) $persist,
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

	$defaults = [
		'method' => 'POST',
		'body'   => [
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