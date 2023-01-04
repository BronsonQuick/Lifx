<?php
namespace Lifx\List_Lights;

/**
 * A function to list all the lights associated with your LIFX Cloud account.
 *
 * @param string  $selector (Optional) Selector used to filter lights. Defaults to `all`.
 *
 * @return array[]|mixed|\WP_Error
 */
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

/**
 * A function to get the number of zones for lights that support it.
 * @param $selector
 *
 * @return array
 */
function zones( $selector = 'all' ) {
	$lights = list_lights( $selector );

	if ( ! empty( $lights ) ) {
		$zones = [];
		foreach ( $lights as $light ) {
			if ( array_key_exists( 'zones', $light ) ) {
				$zones[] = [
					'zones' => $light['zones']['count'],
					'label' => $light['label']
				];
			}
		}
	}

	return $zones;
}