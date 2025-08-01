<?php

namespace WebGenerator\WpCliTraits;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

use WebGenerator\GeneratorUtilities;
use WP_CLI;

trait SiteFavicon {

   /**
    * Upload and set site favicon
    *
    * @param mixed $args
    * @param mixed $assoc_args
    * @return void
    */
  public function favicon( $args, $assoc_args ) {
    $apidata = $this->appWpTokenGet( $assoc_args );
    if( !$apidata ) return;

    $retdata = $apidata->client;
    $divi = (array) json_decode( json_encode($apidata->divi), true );

    $thefavicon = $retdata->favicon;
    $favicon = GeneratorUtilities::upload_file_by_url(
      $thefavicon,
      sanitize_title( $retdata->site_name . "-favicon" ),
      sanitize_title( $retdata->site_name . "-favicon" )
    );
    
    if( $favicon ) {
      update_option( 'site_icon', $favicon );
      WP_CLI::success( __( 'Logo has been set, attachment ID: ' . $favicon, 'adiosgenerator' ) );
    } else {
      WP_CLI::error( __( 'Failed to set favicon', 'adiosgenerator' ) );
    }
  }
}

