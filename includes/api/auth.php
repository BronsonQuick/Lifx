<?php
namespace Lifx\Auth;
use Lifx\Options_Page;

function get_token() {
	if ( defined( 'LIFX_TOKEN' ) ) {
		return LIFX_TOKEN;
	}
	return Options_Page\get_option( 'lifx_token' );
}

function check_token() {
	$headers = get_headers();

	if ( is_wp_error( $headers ) ) {
		return $headers;
	}

	$endpoint = LIFX_ENDPOINT . '/lights/all';

	$response = wp_safe_remote_get(
		$endpoint,
		$headers
	);

	if ( is_wp_error( $response ) ) {
			$result['status'] = 'critical';

			$result['label'] = __( 'The Lifx API encountered an error' );

			$result['description'] .= sprintf(
				'<p>%s</p><p>%s<br>%s</p>',
				__( 'When testing the Lifx API, an error was encountered:' ),
				sprintf(
					// translators: %s: The Lifx API URL.
					__( 'Lifx API Endpoint: %s' ),
					$endpoint
				),
				sprintf(
					// translators: 1: The WordPress error code. 2: The WordPress error message.
					__( 'Lifx API Response: (%1$s) %2$s' ),
					$response->get_error_code(),
					$response->get_error_message()
				)
			);
	} elseif ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
		$result['status'] = 'recommended';

		$result['label'] = __( 'The Lifx API encountered an unexpected result' );

		$result['description'] .= sprintf(
			'<p>%s</p><p>%s<br>%s</p>',
			__( 'When testing the Lifx API, an unexpected result was returned:' ),
			sprintf(
				// translators: %s: The Lifx API URL.
				__( 'Lifx API Endpoint: %s' ),
				$endpoint
			),
			sprintf(
				// translators: 1: The WordPress error code. 2: The HTTP status code error message.
				__( 'Lifx API Response: (%1$s) %2$s' ),
				wp_remote_retrieve_response_code( $response ),
				wp_remote_retrieve_response_message( $response )
			)
		);
		} else {
			return new \WP_HTTP_Response( __('Successfully authenticated your Lifx Personal Access Token.', 'lifx' ), 200 );
		}
}

function get_headers() {
	$token = get_token();

	if ( false === $token ) {
		return new \WP_Error( 'no_token', 'Please either save your Lifx Personal Access token or define the LIFX_TOKEN constant.' );
	}

	return [
		'headers' => [
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $token,
		]
	];
}