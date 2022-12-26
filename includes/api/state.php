<?php
namespace Lifx\State;

/**
 * A function that we can use to set the state of the lamp.
 * https://api.developer.lifx.com/reference/set-state
 * 
 * @param $payload
 * @param $selector
 *
 * @return array[]|mixed|\WP_Error
 */
function state( $payload, $selector = 'all' ) {
	$headers = \Lifx\Auth\get_headers();

	if ( is_wp_error( $headers ) ) {
		return $headers;
	}

	$endpoint = LIFX_ENDPOINT . "/lights/$selector/state";

	$defaults = [
		'method' => 'PUT',
		'body' => [
			'power'  => 'on',
			'fast'   => (bool) true,
		],
	];

	$payload = array_merge( $defaults, $payload, $headers );

	$payload['body'] = wp_json_encode( $payload['body'] );

	$request = wp_safe_remote_post(
		$endpoint,
		$payload
	);

	$response = json_decode( wp_remote_retrieve_body( $request ), true );

	return $response;
}