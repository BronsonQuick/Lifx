<?php
namespace Lifx\List_Lights;

function list_lights( $selector = 'all' ) {
	$headers = \Lifx\Auth\get_headers();

	$endpoint = LIFX_ENDPOINT . "/lights/$selector";

	if ( is_wp_error( $headers ) ) {
		return $headers;
	}

	$lights = wp_safe_remote_get(
		$endpoint,
		$headers
	);

	$response = json_decode( wp_remote_retrieve_body( $lights ), true );

	return $response;
}