<?php

namespace WebGenerator\WpCliTraits;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

use WebGenerator\GeneratorUtilities;
use WebGenerator\GeneratorAPI;
use WP_CLI;
use WebGenerator\GeneratorLogging;

trait SEOPages {

  private function process_post_seo( $token, $apidata, $post ) {

    $content = $post->post_content;
    preg_match('/<p>(.*?)<\/p>/is', $content, $matches);
    $seoContent = isset($matches[1]) ? $matches[1] : '';
    if( empty( $seoContent ) ) {
      return false;
    }

    $instructions = "Title: {$post->post_title}\n\nContent:{$seoContent}\n";
    $apiseo = GeneratorAPI::run(
      GeneratorAPI::generatorapi( "/api/trpc/openai.askseo" ),
      array(
        "instructions" => $instructions
      ),
      $token
    );

    $apidata = GeneratorAPI::getResponse( $apiseo );
    update_post_meta( $front_page_id, '_wds_metadesc', $apidata->seo_description );
    update_post_meta( $front_page_id, '_wds_focus-keywords', implode( ",", $apidata->seo_keywords ) );
  }

  public function process_seo_pages( $args, $assoc_args ) {
    $apidata = $this->appWpTokenGet( $assoc_args );
    if( !$apidata ) return;
    $token = $assoc_args['token'];
    
    $posts = get_posts(array(
      'posts_per_page' => -1,
      'post_type' => array(
        "page",
        "post",
        "project",
        "diva_services"
      )
    ));

    foreach( $posts  as $post ) {
      $this->process_post_seo( $token, $apidata, $post );
    }

    WP_CLI::success( __( 'SEO pages has been generated. ', 'adiosgenerator' ) );
  }
}