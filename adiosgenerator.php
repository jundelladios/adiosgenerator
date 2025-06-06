<?php

/**
 * Plugin Name: AD-IOS Web Generator
 * Description: Effortlessly connect your WordPress site to a powerful centralized dashboard for seamless management and monitoring. This plugin enables real-time synchronization between your website and the web generator. 
 * Version: 1.2.4
 * Author: AD-IOS Web Development
 * Author URI: https://ad-ios.com/support/
 * License: GPL-2.0+
 * Text Domain: adiosgenerator
 * Domain Path: /languages
 * Tested up to: 6.3
 * Requires PHP: 8.1
 */

require __DIR__ . '/vendor/autoload.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
PucFactory::buildUpdateChecker(
  'https://github.com/jundelladios/adiosgenerator',
  __FILE__,
  'adiosgenerator'
);

defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

define("ADIOSGENERATOR_PLUGIN_URI", trailingslashit( plugin_dir_url( __FILE__ ) ) );

define("ADIOSGENERATOR_PLUGIN_DIR", trailingslashit( plugin_dir_path( __FILE__ ) ) );

if( file_exists( ABSPATH . 'wp-adiosgenerator-config.php' ) ) {
	require_once ABSPATH . 'wp-adiosgenerator-config.php';
} else {
	define("ADIOSGENERATOR_API_URL", "https://adios-webgenerator.com");
	define("ADIOSGENERATOR_LOGOMAKER_URL", "https://adios-webgenerator.com");
}

require_once ADIOSGENERATOR_PLUGIN_DIR . "includes/class-utils.php";

require_once ADIOSGENERATOR_PLUGIN_DIR . "includes/class-process-content.php";

require_once ADIOSGENERATOR_PLUGIN_DIR . "includes/class-api.php";

require_once ADIOSGENERATOR_PLUGIN_DIR . "includes/class-sso.php";

require_once ADIOSGENERATOR_PLUGIN_DIR . "includes/supports/class-gravityforms.php";

require_once ADIOSGENERATOR_PLUGIN_DIR . "includes/class-optimization.php";

require_once ADIOSGENERATOR_PLUGIN_DIR . "includes/class-cache.php";

require_once ADIOSGENERATOR_PLUGIN_DIR . "includes/class-cli.php";