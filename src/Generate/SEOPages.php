<?php

namespace WebGenerator\Generate;

use WebGenerator\RestAPIs\Generate;

use WebGenerator\GeneratorAPI;
use WebGenerator\GeneratorUtilities;
use ET_Core_PageResource;

class SEOPages extends Generate {

  private $event;

  public function __construct() {
    $this->event = $this->setEvent( "seo_pages" );
  }

  public function getEvent() {
    return $this->event;
  }

  /**
    * generate page and post contents from AI
    * @return void
    */
  public function execute() {
    $apidata = $this->get_client();
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
      $this->process_post_seo( $apidata, $post );
    }
    update_option( $this->getEvent(), 1 );
  }
  
  public function process_post_seo( $apidata, $post ) {
    $content = $post->post_content;
    preg_match('/<(p)\b[^>]*>(.*?)<\/p>/is', $content, $matches);
    $seoContent = isset($matches[2]) ? $matches[2] : '';
    if( empty( $seoContent ) ) {
      return false;
    }

    $instructions = "Title: {$post->post_title}\n\nContent:{$seoContent}\n";
    $apiseo = GeneratorAPI::run(
      GeneratorAPI::generatorapi( "/api/trpc/openai.askseo" ),
      array(
        "instructions" => $instructions
      )
    );

    $apidata = GeneratorAPI::getResponse( $apiseo );
    update_post_meta( $post->ID, '_wds_metadesc', $apidata->seo_description ?? "" );
    update_post_meta( $post->ID, '_wds_focus-keywords', implode( ",", !is_array($apidata->seo_keywords) ? [] : $apidata->seo_keywords ) );
  }
}