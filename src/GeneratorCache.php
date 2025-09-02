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
    # clear cache humingbird
    do_action( 'wphb_clear_page_cache' );
    
    if(class_exists("ET_Core_PageResource")) {
      ET_Core_PageResource::remove_static_resources( "all", "all" );
      et_core_clear_transients();
      et_core_clear_wp_cache();
    }

    # clear object cache
    wp_cache_flush();
  }
}