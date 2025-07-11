<?php

namespace WebGenerator\WpCliTraits;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

use WebGenerator\GeneratorUtilities;
use WebGenerator\GeneratorAPI;
use WebGenerator\GeneratorCache;
use ET_Core_PageResource;
use WP_CLI;

trait AIGenerateContent {

   /**
    * Reusable code to handle content replacement from AI
    *
    * @param mixed $apidata
    * @param string $token
    * @param string $pattern A valid regex pattern
    * @param int $postId
    * @param integer $maxSnippet
    * @param string $type
    * @return void
    */
  private function ai_content_generate( $apidata, $token, $pattern, $postId, $maxSnippet = 20, $type = "sentence" ) {

    $retdata = $apidata->client;

    $post = get_post( $postId );
    $content = $post->post_content;

    preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
    $excludeWords = [
      "contact",
      "services",
      "quicklinks",
      "sitemap",
      "get in touch",
      "working hours",
      $retdata->site_name,
      $retdata->slogan,
      $retdata->contact_number,
      $retdata->email_address,
      str_replace( " ", "+", $retdata->site_address ),
      $retdata->site_address,
      $retdata->insights
    ];

    $aiContentRequest = array();
    $replaceContents = array();
    foreach( $matches as $key => $match ) {
      $matchExclude = false;
      foreach ($excludeWords as $needle) {
        if (isset($match[2]) && stripos($match[2], $needle) !== false) {
          $matchExclude = true;
          break;
        }
      }

      if( isset( $match[2]) && !empty( $match[2]) && !$matchExclude ) {
        $countWords = count(explode(" ", $match[2]));
        $countWords = $countWords > $maxSnippet ? $maxSnippet : $countWords;
        $type = $countWords < 3 ? "heading title" : $type;
        $aiContentRequest[] = "Write a blog {$type} with the max amount of {$countWords} words — not fewer, not more. It must a proper casing.";
        $replaceContents[] = $match[2];
      }
    }

    $contents = GeneratorAPI::run(
      GeneratorAPI::generatorapi( "/api/trpc/openai.askcontent" ),
      array(
        "instructions" => json_encode( $aiContentRequest ),
        "max" => count( $aiContentRequest ),
        "maxtext" => $maxSnippet
      ),
      $token
    );
    $apidata = GeneratorAPI::getResponse( $contents );
    $snippetContents = $apidata->snippets;

    foreach( $replaceContents as $key => $rpcontent ) {
      if( isset( $snippetContents[$key] ) ) {
        $content = str_replace( $rpcontent, $snippetContents[$key], $content );
      }
    }
    
    wp_update_post([
      'ID' => $postId,
      'post_content' => $content
    ]);

    ET_Core_PageResource::do_remove_static_resources( $postId, 'all' );
  }

   /**
    * Cli to generate page and post contents from AI
    *
    * @param mixed $args
    * @param mixed $assoc_args
    * @return void
    */
  public function generate_ai_content( $args, $assoc_args ) {
    $apidata = $this->appWpTokenGet( $assoc_args );
    if( !$apidata ) return;
    $token = $assoc_args['token'];
    $retdata = $apidata->client;

    $posts = get_posts(array(
      'posts_per_page' => -1,
      'post_type' => array(
        "page",
        "post",
        "project"
      )
    ));

    foreach( $posts as $post ) {
      $this->ai_content_generate(
        $apidata,
        $token,
        '/<(p)\b[^>]*>(.*?)<\/\1>/is',
        $post->ID,
        350
      );

      // replace headings except h1 because h1 used to be the site name by default or internal pages title
      $this->ai_content_generate(
        $apidata,
        $token,
        '/<(h[2-6])\b[^>]*>(.*?)<\/\1>|\\b(title|heading)="([^"]*)"/is',
        $post->ID,
        30,
        "heading title"
      );

      ET_Core_PageResource::do_remove_static_resources( $post->ID, 'all' );
    }
  }
}