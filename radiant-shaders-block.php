<?php
/**
 * Plugin Name:       Radiant Shaders Block
 * Plugin URI:        https://github.com/mattwiebe/radiant-shaders-block
 * Description:       Animated Radiant shader backgrounds for the block editor.
 * Version:           0.1.0
 * Requires at least: 6.6
 * Requires PHP:      7.2
 * Author:            Matt Wiebe
 * License:           MIT
 * Text Domain:       radiant-shaders-block
 *
 * @package RadiantShadersBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RADIANT_SHADERS_BLOCK_FILE', __FILE__ );
define( 'RADIANT_SHADERS_BLOCK_DIR', plugin_dir_path( __FILE__ ) );
define( 'RADIANT_SHADERS_BLOCK_URL', plugin_dir_url( __FILE__ ) );

require_once RADIANT_SHADERS_BLOCK_DIR . 'includes/class-radiant-shaders-block-plugin.php';

\RadiantShadersBlock\Plugin::init();
