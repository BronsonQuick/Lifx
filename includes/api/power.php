<?php
namespace Lifx\Power;

function toggle_lights( $selector = 'all' ) {
	$headers = \Lifx\Auth\get_headers();

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

function power( $state = 'on', $selector = 'all', $fast = false ) {
	$headers = \Lifx\Auth\get_headers();

	if ( is_wp_error( $headers ) ) {
		return $headers;
	}

	$endpoint = LIFX_ENDPOINT . "/lights/$selector/state";

	$body = [
		'method' => 'PUT',
		'body' => json_encode(
			[
				'power' => $state,
				'fast'  => (bool) $fast,
			]
		)
	];

	$payload = array_merge( $headers, $body );

	$toggle = wp_safe_remote_post(
		$endpoint,
		$payload
	);

	$response = json_decode( wp_remote_retrieve_body( $toggle ), true );

	return $response;
}