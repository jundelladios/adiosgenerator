<?php

/**
 * Plugin Name: AD-IOS Web Generator
 * Description: Web Generator from AD-IOS Team
 * Version: 1.0.0
 * Author: AD-IOS Web Development
 * Author URI: https://ad-ios.com/website-support-ticket
 * License: GPL-2.0+
 * Text Domain: adiosgenerator
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

define("ADIOSGENERATOR_PLUGIN_URI", trailingslashit( plugin_dir_url( __FILE__ ) ) );

define("ADIOSGENERATOR_PLUGIN_DIR", trailingslashit( plugin_dir_path( __FILE__ ) ) );

if( file_exists( ABSPATH . 'wp-adiosgenerator-config.php' ) ) {
	require_once ABSPATH . 'wp-adiosgenerator-config.php';
} else {
	define("ADIOSGENERATOR_API_URL", "https://adios-webgenerator.com");
}

require_once ADIOSGENERATOR_PLUGIN_DIR . "includes/class-utils.php";

require_once ADIOSGENERATOR_PLUGIN_DIR . "includes/class-api.php";

require_once ADIOSGENERATOR_PLUGIN_DIR . "includes/class-cli.php";

require_once ADIOSGENERATOR_PLUGIN_DIR . "includes/class-sso.php";