<?php

namespace WebGenerator\WpCliTraits;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

use WebGenerator\GeneratorCache;
use WP_CLI;

trait ClearCache {

   /**
    * WP CLI command for adiosgenerator cache clear
    *
    * @return void
    */
  public function clear() {
    GeneratorCache::clear_cache();
    WP_CLI::success( __( 'All cache has been cleared!', 'adiosgenerator' ) );
  }
}