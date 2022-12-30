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

/**
 * A function to list activate a LIFX scene.
 *
 * @param string $uuid The UUID of the scene to activate.
 *
 * @return array[]|mixed|\WP_Error
 */
function activate_scene( $uuid, $fast = false ) {
	$headers = \Lifx\Auth\get_headers();

	$endpoint = LIFX_ENDPOINT . "/scenes/scene_id:$uuid/activate";

	if ( is_wp_error( $headers ) ) {
		return $headers;
	}

	$fast = filter_var( $fast, FILTER_VALIDATE_BOOLEAN );

	$defaults = [
		'method'  => 'PUT',
		'timeout' => 10,
		'body'    => [
			'fast' => $fast,
		]
	];

	$payload = array_merge( $defaults, $headers );

	$payload['body'] = wp_json_encode( $payload['body'] );

	$response = wp_safe_remote_get(
		$endpoint,
		$payload
	);

	return $response;
}