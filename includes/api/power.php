<?php
namespace Lifx\Power;

use function \Lifx\Auth\get_headers;
use function Lifx\State\state;

/**
 * A function to toggle the state of the LIFX lights.
 *
 * @param string $selector (Optional) Selector used to filter lights. Defaults to `all`.
 *
 * @return array[]|mixed|\WP_Error
 */
function toggle_lights( $selector = 'all' ) {
	$headers = get_headers();

	if ( is_wp_error( $headers ) ) {
		return $headers;
	}

	$endpoint = LIFX_ENDPOINT . "/lights/$selector/toggle";

	$toggle = wp_safe_remote_post(
		$endpoint,
		$headers
	);

	$response = json_decode( wp_remote_retrieve_body( $toggle ), true );

	return $response;
}

/**
 * A function to turn on or off all lights or a specific light.
 *
 * @param string  $state    (Optional) The state of the power. Defaults to `on`.
 * @param boolean $fast     (Optional) Whether the lights should return a payload or just a status code. Defaults to `false`.
 * @param string  $selector (Optional) Selector used to filter lights. Defaults to `all`.
 * @param integer $duration (Optional) The duration for the change of state. Defaults to `1`.
 *
 * @return array[]|mixed|\WP_Error
 */
function power( $state = 'on', $fast = false, $selector = 'all', $duration = 1 ) {

	$payload = [
		'body' => [
			'power'    => $state,
			'fast'     => $fast,
			'duration' => (int) $duration,
		]
	];

	$request = state( $payload, $selector );

	return $request;
}