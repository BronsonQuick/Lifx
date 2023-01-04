<?php
/**
 * Plugin Name: Lifx For WordPress
 * Plugin URI:  https://github.com/BronsonQuick/Lifx
 * Description: A plugin to control your LIFX lights from WordPress.
 * Author:      Bronson Quick
 * Author URI:  https://bronsonquick.com.au
 * Text Domain: lifx
 * Domain Path: /languages
 * Version:     0.1.0
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * @package     Lifx
 */

namespace Lifx;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define the LIFX endpoint.
defined( 'LIFX_ENDPOINT' ) or define( 'LIFX_ENDPOINT', 'https://api.lifx.com/v1' );

require_once __DIR__ . '/includes/CMB2/init.php';
require_once __DIR__ . '/includes/admin/options-page.php';
require_once __DIR__ . '/includes/api/auth.php';
require_once __DIR__ . '/includes/api/effects.php';
require_once __DIR__ . '/includes/api/list.php';
require_once __DIR__ . '/includes/api/power.php';
require_once __DIR__ . '/includes/api/scenes.php';
require_once __DIR__ . '/includes/api/state.php';
require_once __DIR__ . '/includes/Mexitek/PHPColors/Color.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once __DIR__ . '/includes/commands/class-lifx-command.php';
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\Options_Page\\bootstrap' );