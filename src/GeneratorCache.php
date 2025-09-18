<?php

namespace WebGenerator;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

use WebGenerator\GeneratorAPI;
use ET_Core_PageResource;

class GeneratorCache {

  /**
   * Initialize custom cache settings
   *
   * @return void
   */
  public function init() {
    add_action( 'adiosgenerator_clear_cache', array( $this, 'clear_cache_call' ) );
  }

  /**
   * Handles clear all cache via web generator
   *
   * @return void
   */
  public function clear_cache_call() {
    self::clear_cache();
  }

  /**
   * Handles cloudflare cache clear
   *
   * @return void
   */
  // public static function cloudflare_clear() {
  //   $parsed_url = parse_url(home_url());
  //   $domain = $parsed_url['host'];
  //   GeneratorAPI::run(
  //     GeneratorAPI::generatorapi( "/api/trpc/cw.cloudflareClear" ),
  //     array(
  //       "hostname" => $domain
  //     )
  //   );
  // }

  /**
   * Clear all cache
   *
   * @return void
   */
  public static function clear_cache() {
    // Clear Hummingbird cache
    do_action( 'wphb_clear_page_cache' );

    // Divi cache clear: ensure classes are loaded, especially for WP CLI context
    if ( ! class_exists( 'ET_Core_PageResource' ) ) {
      // Try to load Divi classes if not already loaded (for WP CLI or non-standard contexts)
      if ( function_exists( 'get_template_directory' ) ) {
        $core_dir = get_template_directory() . '/includes/core/';
        $core_file = $core_dir . 'class-et-core-page-resource.php';
        if ( file_exists( $core_file ) ) {
          require_once $core_file;
        }
      }
    }

    if ( class_exists( 'ET_Core_PageResource' ) ) {
      // Use do_remove_static_resources for better compatibility (static method)
      if ( method_exists( 'ET_Core_PageResource', 'do_remove_static_resources' ) ) {
        ET_Core_PageResource::do_remove_static_resources( null, 'all' );
      } elseif ( method_exists( 'ET_Core_PageResource', 'remove_static_resources' ) ) {
        ET_Core_PageResource::remove_static_resources( 'all', 'all' );
      }
      if ( function_exists( 'et_core_clear_transients' ) ) {
        et_core_clear_transients();
      }
      if ( function_exists( 'et_core_clear_wp_cache' ) ) {
        et_core_clear_wp_cache();
      }
    }

    // Clear object cache
    if ( function_exists( 'wp_cache_flush' ) ) {
      wp_cache_flush();
    }
  }
}