<?php
namespace Lifx\Scenes;
/**
 * A function to list all the scenes associated with your LIFX Cloud account.
 *
 * @return array[]|mixed|\WP_Error
 */
function list_scenes() {
	$headers = \Lifx\Auth\get_headers();

	$endpoint = LIFX_ENDPOINT . "/scenes";

	if ( is_wp_error( $headers ) ) {
		return $headers;
	}

	$scenes = wp_safe_remote_get(
		$endpoint,
		$headers
	);

	$response = json_decode( wp_remote_retrieve_body( $scenes ), true );

	return $response;
}