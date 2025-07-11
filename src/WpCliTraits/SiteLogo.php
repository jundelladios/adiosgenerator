<?php

namespace WebGenerator\WpCliTraits;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

use WebGenerator\GeneratorUtilities;
use WP_CLI;
use ET_Core_PageResource;

trait SiteLogo {

   /**
    * Upload and set website main logo
    *
    * @param mixed $args
    * @param mixed $assoc_args
    * @return void
    */
  public function site_logo( $args, $assoc_args ) {
    $apidata = $this->appWpTokenGet( $assoc_args );
    if( !$apidata ) return;

    $retdata = $apidata->client;
    $divi = (array) json_decode( json_encode($apidata->divi), true );

    $thelogo = $retdata->logo;
    $logo = GeneratorUtilities::upload_file_by_url(
      $thelogo,
      sanitize_title( $retdata->site_name . "-logo" ),
      sanitize_title( $retdata->site_name . "-logo" )
    );
    
    if( $logo ) {
      update_option( GeneratorUtilities::et_adiosgenerator_option("logo"), $logo );
      if( function_exists( 'et_update_option') ) {
        et_update_option( "divi_logo", wp_get_attachment_url( $logo ) );
      }
      // disable lazyload and lcp high prio
      update_post_meta( $logo, "adiosgenerator_disable_lazyload", 1 );
      $posts = $this->get_posts_content_generate();

      foreach( $posts as $pst ) {
        $content = $pst->post_content;
        $content = preg_replace('#https?://[^\s\'"]*/site-logo\.[a-zA-Z0-9]+#', wp_get_attachment_url( $logo ), $content);
        wp_update_post([
          'ID' => $pst->ID,
          'post_content' => $content
        ]);
        ET_Core_PageResource::do_remove_static_resources( $pst->ID, 'all' );
      }

      // remove previous logo attachment metadata
      $slogo = GeneratorUtilities::get_attachment_by_post_name( "site-logo" );
      if($slogo) {
        delete_post_meta( $slogo->ID, 'adiosgenerator_disable_lazyload' );
        delete_post_meta( $slogo->ID, 'adiosgenerator_prioritize_background' );
      }

      WP_CLI::success( __( 'Logo has been set, attachment ID: ' . $logo, 'adiosgenerator' ) );
    } else {
      WP_CLI::error( __( 'Failed to set logo', 'adiosgenerator' ) );
    }
  }
}