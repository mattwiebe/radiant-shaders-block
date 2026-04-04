<?php
/**
 * Plugin Name:       WP Radiant Shaders
 * Description:       Animated Radiant shader backgrounds for the block editor.
 * Version:           0.1.0
 * Requires at least: 6.6
 * Requires PHP:      7.2
 * Author:            Matt
 * License:           MIT
 * Text Domain:       wp-radiant-shaders
 *
 * @package WPRadiantShaders
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WP_RADIANT_SHADERS_FILE', __FILE__ );
define( 'WP_RADIANT_SHADERS_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_RADIANT_SHADERS_URL', plugin_dir_url( __FILE__ ) );

require_once WP_RADIANT_SHADERS_DIR . 'includes/class-wp-radiant-shaders-plugin.php';

\WPRadiantShaders\Plugin::init();
