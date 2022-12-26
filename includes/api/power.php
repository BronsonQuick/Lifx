<?php
namespace Lifx\Power;

use function Lifx\State\state;

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

function power( $state = 'on', $fast = false, $selector = 'all' ) {
	$payload = [
		'body' => [
			'power' => $state,
			'fast'  => (bool) $fast,
		]
	];

	$request = state( $payload, $selector );

	return $request;
}