<?php

namespace WebGenerator\WpCliTraits;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

use WebGenerator\GeneratorUtilities;
use WebGenerator\GeneratorCache;
use WP_CLI;

trait SyncTemplate {

   /**
    * Pulls template settings from web generator
    *
    * @param mixed $args
    * @param mixed $assoc_args
    * @return void
    */
  public function sync_template_data( $args, $assoc_args ) {
    $apidata = $this->appWpTokenGet( $assoc_args, "appWpTemplateSync" );
    if( !$apidata ) return;
    $retdata = $apidata->client;
    $divi = (array) json_decode( json_encode($apidata->divi), true );

    /**
     * Elegant themes options
     */
     if( function_exists( 'et_update_option' ) ) {
      foreach ( $divi as $key => $option ) {
        et_update_option( $key, $option );
      }
    }

    GeneratorCache::clear_cache();
    WP_CLI::success( __( 'Data has been synced!', 'adiosgenerator' ) );
  }
}