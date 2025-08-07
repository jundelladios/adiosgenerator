<?php

namespace WebGenerator\WpCliTraits;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

use WebGenerator\GeneratorUtilities;
use WP_CLI;
use ET_Core_PageResource;

trait SiteLogoSecondary {

   /**
    * Upload and set secondary logo of the site, probably not lazyloaded version
    *
    * @param mixed $args
    * @param mixed $assoc_args
    * @return void
    */
  public function site_secondary_logo() {
    $apidata = $this->appWpTokenGet();
    if( !$apidata ) return;

    $retdata = $apidata->client;
    $divi = (array) json_decode( json_encode($apidata->divi), true );

    $thelogo = $retdata->logo;
    $logo = GeneratorUtilities::upload_file_by_url(
      $thelogo,
      sanitize_title( $retdata->site_name . "-logo-alternative" ),
      sanitize_title( $retdata->site_name . "-logo-alternative" )
    );
    
    if( $logo ) {
      update_option( GeneratorUtilities::et_adiosgenerator_option("logo_2"), $logo );
      $posts = $this->get_posts_content_generate();

      foreach( $posts as $pst ) {
        $content = $pst->post_content;
        $content = preg_replace('#https?://[^\s\'"]*/site-logo-secondary\.[a-zA-Z0-9]+#', wp_get_attachment_url( $logo ), $content);
        wp_update_post([
          'ID' => $pst->ID,
          'post_content' => $content
        ]);
        ET_Core_PageResource::do_remove_static_resources( $pst->ID, 'all' );
      }

      WP_CLI::success( __( 'Logo has been set, attachment ID: ' . $logo, 'adiosgenerator' ) );
    } else {
      WP_CLI::error( __( 'Failed to set alternative logo', 'adiosgenerator' ) );
    }
  }
}