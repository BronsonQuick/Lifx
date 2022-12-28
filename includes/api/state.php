<?php
namespace Lifx\State;
use function \Lifx\Auth\get_headers;
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
	$headers = get_headers();

	if ( is_wp_error( $headers ) ) {
		return $headers;
	}

	$endpoint = LIFX_ENDPOINT . "/lights/$selector/state";

	$defaults = [
		'method'  => 'PUT',
		'timeout' => 10,
		'body'    => [
			'power' => 'on',
			'fast'  => false,
		],
	];

	$payload = array_merge( $defaults, $payload, $headers );

	// Make sure we change the type to a boolean.
	$payload['body']['fast'] = filter_var( $payload['body']['fast'], FILTER_VALIDATE_BOOLEAN );

	$payload['body'] = wp_json_encode( $payload['body'] );

	$request = wp_safe_remote_post(
		$endpoint,
		$payload
	);

	return $request;
}

/**
 * A function to get the colours we've setup for our lights.
 *
 * We are using the web browser HTML colours: https://www.quackit.com/html/codes/color/html_color_chart.cfm
 *
 * LIFX's API supports the following strings: 'white, red, orange, yellow, cyan, green, blue, purple, or pink'
 * However their version of 'Pink' is closer to a 'DeepPink' so let's stick to the names web browsers
 * and developers are used to.
 *
 * @return array
 */
function get_colours() {
	$colours = [
		// Reds.
		'indianred'            => '#CD5C5C',
		'lightcoral'           => '#F08080',
		'salmon'               => '#FA8072',
		'darksalmon'           => '#E9967A',
		'lightsalmon'          => '#FFA07A',
		'crimson'              => '#DC143C',
		'red'                  => '#FF0000',
		'firebrick'            => '#B22222',
		'darkred'              => '#8B0000',
		// Pinks.
		'pink'                 => '#FFC0CB',
		'lightpink'            => '#FFB6C1',
		'hotpink'              => '#FF69B4',
		'deeppink'             => '#FF1493',
		'mediumvioletred'      => '#C71585',
		'palevioletred'        => '#DB7093',
		// Oranges.
		'coral'                => '#FF7F50',
		'tomato'               => '#FF6347',
		'orangered'            => '#FF4500',
		'darkorange'           => '#FF8C00',
		'orange'               => '#FFA500',
		// Yellows.
		'gold'                 => '#FFD700',
		'yellow'               => '#FFFF00',
		'lightyellow'          => '#FFFFE0',
		'lemonchiffon'         => '#FFFACD',
		'lightgoldenrodyellow' => '#FAFAD2',
		'papayawhip'           => '#FFEFD5',
		'moccasin'             => '#FFE4B5',
		'peachpuff'            => '#FFDAB9',
		'palegoldenrod'        => '#EEE8AA',
		'khaki'                => '#F0E68C',
		'darkkhaki'            => '#BDB76B',
		// Purples.
		'lavender'             => '#E6E6FA',
		'thistle'              => '#D8BFD8',
		'plum'                 => '#DDA0DD',
		'violet'               => '#EE82EE',
		'orchid'               => '#DA70D6',
		'fuchsia'              => '#FF00FF',
		'magenta'              => '#FF00FF',
		'mediumorchid'         => '#BA55D3',
		'mediumpurple'         => '#9370DB',
		'blueviolet'           => '#8A2BE2',
		'darkviolet'           => '#9400D3',
		'darkorchid'           => '#9932CC',
		'darkmagenta'          => '#8B008B',
		'purple'               => '#800080',
		'rebeccapurple'        => '#663399',
		'indigo'               => '#4B0082',
		'mediumslateblue'      => '#7B68EE',
		'slateblue'            => '#6A5ACD',
		'darkslateblue'        => '#483D8B',
		// Greens.
		'greenyellow'          => '#ADFF2F',
		'chartreuse'           => '#7FFF00',
		'lawngreen'            => '#7CFC00',
		'lime'                 => '#00FF00',
		'limegreen'            => '#32CD32',
		'palegreen'            => '#98FB98',
		'lightgreen'           => '#90EE90',
		'mediumspringgreen'    => '#00FA9A',
		'springgreen'          => '#00FF7F',
		'mediumseagreen'       => '#3CB371',
		'seagreen'             => '#2E8B57',
		'forestgreen'          => '#228B22',
		'green'                => '#008000',
		'darkgreen'            => '#006400',
		'yellowgreen'          => '#9ACD32',
		'olivedrab'            => '#6B8E23',
		'olive'                => '#808000',
		'darkolivegreen'       => '#556B2F',
		'mediumaquamarine'     => '#66CDAA',
		'darkseagreen'         => '#8FBC8F',
		'lightseagreen'        => '#20B2AA',
		'darkcyan'             => '#008B8B',
		'teal'                 => '#008080',
		// Blues/Cyans.
		'aqua'                 => '#00FFFF',
		'cyan'                 => '#00FFFF',
		'lightcyan'            => '#E0FFFF',
		'paleturquoise'        => '#AFEEEE',
		'aquamarine'           => '#7FFFD4',
		'turquoise'            => '#40E0D0',
		'mediumturquoise'      => '#48D1CC',
		'darkturquoise'        => '#00CED1',
		'cadetblue'            => '#5F9EA0',
		'steelblue'            => '#4682B4',
		'lightsteelblue'       => '#B0C4DE',
		'powderblue'           => '#B0E0E6',
		'lightblue'            => '#ADD8E6',
		'skyblue'              => '#87CEEB',
		'lightskyblue'         => '#87CEFA',
		'deepskyblue'          => '#00BFFF',
		'dodgerblue'           => '#1E90FF',
		'cornflowerblue'       => '#6495ED',
		'royalblue'            => '#4169E1',
		'blue'                 => '#0000FF',
		'mediumblue'           => '#0000CD',
		'darkblue'             => '#00008B',
		'navy'                 => '#000080',
		'midnightblue'         => '#191970',
		// Browns
		'cornsilk'             => '#FFF8DC',
		'blanchedalmond'       => '#FFEBCD',
		'bisque'               => '#FFE4C4',
		'navajowhite'          => '#FFDEAD',
		'wheat'                => '#F5DEB3',
		'burlywood'            => '#DEB887',
		'tan'                  => '#D2B48C',
		'rosybrown'            => '#BC8F8F',
		'sandybrown'           => '#F4A460',
		'goldenrod'            => '#DAA520',
		'darkgoldenrod'        => '#B8860B',
		'peru'                 => '#CD853F',
		'chocolate'            => '#D2691E',
		'saddlebrown'          => '#8B4513',
		'sienna'               => '#A0522D',
		'brown'                => '#A52A2A',
		'maroon'               => '#800000',
		// Whites.
		'white'                => '#ffffff',
		'snow'                 => '#FFFAFA',
		'honeydew'             => '#F0FFF0',
		'mintcream'            => '#F5FFFA',
		'azure'                => '#F0FFFF',
		'aliceblue'            => '#F0F8FF',
		'ghostwhite'           => '#F8F8FF',
		'whitesmoke'           => '#F5F5F5',
		'seashell'             => '#FFF5EE',
		'beige'                => '#F5F5DC',
		'oldlace'              => '#FDF5E6',
		'floralwhite'          => '#FFFAF0',
		'ivory'                => '#FFFFF0',
		'antiquewhite'         => '#FAEBD7',
		'linen'                => '#FAF0E6',
		'lavenderblush'        => '#FFF0F5',
		'mistyrose'            => '#FFE4E1',
		// Greys.
		'gainsboro'            => '#DCDCDC',
		'lightgray'            => '#D3D3D3',
		'lightgrey'            => '#D3D3D3',
		'silver'               => '#C0C0C0',
		'darkgray'             => '#A9A9A9',
		'darkgrey'             => '#A9A9A9',
		'gray'                 => '#808080',
		'grey'                 => '#808080',
		'dimgray'              => '#696969',
		'dimgrey'              => '#696969',
		'lightslategray'       => '#778899',
		'lightslategrey'       => '#778899',
		'slategray'            => '#708090',
		'slategrey'            => '#708090',
		'darkslategray'        => '#2F4F4F',
		'darkslategrey'        => '#2F4F4F',
		'black'                => '#000000',
	];

	/**
	 * A filter to allow custom colours.
	 *
	 * @param array $colours The array of supported colours.
	 */
	$colours = apply_filters( 'lifx_colours', $colours );

	return (array) $colours;
}

/**
 * @param string $colour The colour to set the light to. This takes a few formats. i.e. rebeccapurple, random, "#336699", "hue:120 saturation:1.0 brightness:0.5"
 * Full docs are here: https://api.developer.lifx.com/docs/colors
 * @param boolean $fast    (Optional) Whether the lights should return a payload or just a status code. Defaults to `false`.
 * @param string $selector (Optional) Selector used to filter lights. Defaults to `all`.
 *
 * @return array|array[]|mixed|\WP_Error
 */
function colour( $colour, $fast = false, $selector = 'all' ) {

	$colour_string = validate_web_colours( $colour );

	$fast = filter_var( $fast, FILTER_VALIDATE_BOOLEAN );

	// Set the colour
	$payload = [
		'body' => [
			'power' => 'on',
			'fast'  => $fast,
			'color' => $colour_string
		]
	];

	$request = state( $payload, $selector );

	return $request;
}

/**
 * A function to validate strings and colours passed into our functions with the LIFX API.
 * https://api.developer.lifx.com/reference/validate-color
 *
 * @param string $colour
 *
 * @return array|array[]|\WP_Error
 */
function validate_colour( $colour ) {
	$headers = get_headers();

	if ( is_wp_error( $headers ) ) {
		return $headers;
	}

	$endpoint = LIFX_ENDPOINT . "/color";

	$defaults = [
		'method'  => 'GET',
		'timeout' => 10,
		'body'    => [
			'string' => $colour,
		],
	];

	$payload = array_merge( $defaults, $headers );

	$request = wp_safe_remote_post(
		$endpoint,
		$payload
	);

	if ( 200 !== wp_remote_retrieve_response_code( $request ) ) {
		return new \WP_Error( '422', "$colour is not a valid LIFX colour." );
	}

	return $request;
}

/**
 * A function to change the brightness of all lights or specific lights.
 *
 * @param float   $brightness The brightness level from 0.0 to 1.0. Overrides any brightness set in color (if any).
 * @param boolean $fast       (Optional) Whether the lights should return a payload or just a status code. Defaults to `false`.
 * @param string  $selector   (Optional) Selector used to filter lights. Defaults to `all`.
 *
 * @return array[]|mixed|\WP_Error
 */
function brightness( $brightness, $fast = false, $selector = 'all' ) {
	$fast = filter_var( $fast, FILTER_VALIDATE_BOOLEAN );

	// Set the brightness
	$payload = [
		'body' => [
			'power'      => 'on',
			'fast'       => $fast,
			'brightness' => (float) $brightness,
		]
	];

	$request = state( $payload, $selector );

	return $request;
}

/**
 * A function to see if the colour string is registered in our plugin.
 * If not, validate it with the LIFX API. https://api.developer.lifx.com/reference/validate-color
 *
 * @param $colour
 *
 * @return array|array[]|mixed|string|\WP_Error
 */
function validate_web_colours( $colour ) {
	// Change the colour to lower case. e.g. HotPink becomes 'hotpink'.
	$colour = strtolower( $colour );

	$colours = get_colours();

	$colour_string = '';

	// If the colour is in our list of colours then set the colour to the Hex value.
	if ( true === array_key_exists( $colour, $colours ) ) {
		$colour_string = $colours[$colour];
	}

	/**
	 * If the colour name or hex values doesn't exist then we should validate it via the LIFX API.
	 * https://api.developer.lifx.com/reference/validate-color
	 */
	if ( empty( $colour_string ) && false === in_array( $colour, $colours, true ) ) {
		$validation = validate_colour( $colour );
			if ( is_wp_error( $validation ) ) {
			return $validation;
		}
		$colour_string = $colour;
	}

	return $colour_string;
}