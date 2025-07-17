<?php

namespace WebGenerator\WpCliTraits;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

use WebGenerator\GeneratorUtilities;
use WP_CLI;
use WebGenerator\GeneratorLogging;

trait ServicesPages {

  public function process_services_pages( $args, $assoc_args ) {
    $apidata = $this->appWpTokenGet( $assoc_args );
    if( !$apidata ) return;

    $client = $apidata->client;
    
    $firstDraftSpage = get_posts( array(
      'post_type' => 'diva_services',
      'numberposts' => 1,
      'post_status' => 'draft'
    ));

    $template = null;
    if( !empty( $firstDraftSpage ) ) {
      $template = $firstDraftSpage[0];
    }

    if( !$template ) {
      WP_CLI::error( __( 'No draft service page found. Please create a draft service page first.', 'adiosgenerator' ) );
      return;
    }
    
    $services = explode( ',', $client->services ?? '' );
    foreach( $services as $service ) {
      $service = trim( $service );
      $postExists = get_posts( array(
        'post_type' => 'diva_services',
        'title' => $service,
        'numberposts' => 1,
        'post_status'    => 'any',
      ));
      if( empty( $postExists)) {
        $post_id = GeneratorUtilities::duplicate_post( $template->ID, $service, "publish" );
        $post = get_post( $post_id );
        $content = $post->post_content;
        $content = str_replace(
          $template->post_title,
          $post->post_title,
          $content
        );
        wp_update_post([
          'ID' => $post->ID,
          'post_content' => $content
        ]);
      }
    }

    WP_CLI::success( __( 'Services pages has been added!', 'adiosgenerator' ) );

  }
}