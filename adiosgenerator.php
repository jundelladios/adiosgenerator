<?php
/**
 * Plugin Name: AD-IOS Web Generator
 * Description: Connect your WordPress site to a powerful centralized dashboard for seamless management and monitoring. This plugin enables real-time synchronization between your website and the web generator. 
 * Version: 6.0.1
 * Text Domain: adiosgenerator
 * Author: AD-IOS Digital Marketing Co.
 * Author URI: https://ad-ios.com/
 * License: GPL-2.0+
 * Domain Path: /languages
 */
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

require __DIR__ . '/vendor/autoload.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
use WebGenerator\GeneratorCLI;
use WebGenerator\GeneratorGravityForms;
use WebGenerator\GeneratorSSO;
use WebGenerator\GeneratorREST;
use WebGenerator\GeneratorOptimization;
use WebGenerator\GeneratorCache;
use WebGenerator\GeneratorMime;
use WebGenerator\GeneratorMedia;
use WebGenerator\GeneratorAdminBar;
use WebGenerator\GeneratorServicesPages;
use WebGenerator\GeneratorPostSettings;
use WebGenerator\GeneratorSettings;
use WebGenerator\GeneratorStepper;


PucFactory::buildUpdateChecker(
  'https://github.com/jundelladios/adiosgenerator',
  __FILE__,
  'adiosgenerator'
);

define("ADIOSGENERATOR_PLUGIN_URI", trailingslashit( plugin_dir_url( __FILE__ ) ) );

define("ADIOSGENERATOR_PLUGIN_DIR", trailingslashit( plugin_dir_path( __FILE__ ) ) );

if( file_exists( ABSPATH . 'wp-adiosgenerator-config.php' ) ) {
	require_once ABSPATH . 'wp-adiosgenerator-config.php';
} else {
	define("ADIOSGENERATOR_API_URL", "https://adios-webgenerator.com");
}

// ensure plugin requires divi theme and activated
add_action( 'plugins_loaded', 'adiosgenerator_plugin_check_dependencies' );
function adiosgenerator_plugin_check_dependencies() {
  $theme = wp_get_theme();
  if ( !($theme->get( 'Name' ) === 'Divi' || $theme->get_template() === 'Divi') ) {
    // auto deactivate plugin
    deactivate_plugins( plugin_basename( __FILE__ ) );
    // show notice
    add_action( 'admin_notices', function () {
      echo '
        <div class="notice notice-error">
          <p><strong>AD-IOS Web Generator:</strong> Divi Theme is required. Plugin deactivated.
          </p>
        </div>';
    });
    // prevent further loading
    return;
  }
}

// force support post thumbnails
add_theme_support('post-thumbnails');

// CLI
if (defined('WP_CLI') && WP_CLI) {
	WP_CLI::add_command(
		'adiosgenerator',
		GeneratorCLI::class
	);
}

// Gravity Forms
(new GeneratorGravityForms)->init();

// SSO
function adiosgenerator_initialize_sso() {
  $token = isset( $_GET["wpgentoken"] ) ? $_GET["wpgentoken"] : "";
  $redirect = isset( $_GET["wpgenredirect"] ) ? $_GET["wpgenredirect"] : "";
  $post = isset( $_GET["post"] ) ? $_GET["post"] : "";
  GeneratorSSO::init( $token, $redirect, $post );
}
add_action('plugins_loaded', "adiosgenerator_initialize_sso");

// process content api
(new GeneratorREST)->routes();

// optimization
(new GeneratorOptimization)->init();

// caching
(new GeneratorCache)->init();

// admin bar
(new GeneratorAdminBar)->init();

// mimes
(new GeneratorMime)->init();

// media admin label
// (new GeneratorMedia)->init();

// services pages
(new GeneratorServicesPages)->init();

// post settings
(new GeneratorPostSettings)->init();

// settings
(new GeneratorSettings)->init();

// stepper feature
(new GeneratorStepper)->init();

// divi custom modules
require_once constant('ADIOSGENERATOR_PLUGIN_DIR') . 'divi-extensions/divi-extensions.php';