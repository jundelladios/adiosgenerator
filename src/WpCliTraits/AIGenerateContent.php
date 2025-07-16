<?php

namespace WebGenerator\WpCliTraits;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

use WebGenerator\GeneratorUtilities;
use WebGenerator\GeneratorAPI;
use WebGenerator\GeneratorCache;
use WebGenerator\GeneratorLogging;
use ET_Core_PageResource;
use WP_CLI;

trait AIGenerateContent {

   /**
    * Reusable code to handle content replacement from AI
    *
    * @param mixed $apidata
    * @param string $token
    * @param int $postId
    * @return void
    */
  private function ai_content_generate( $apidata, $token, $post ) {

    $retdata = $apidata->client;
    $content = $post->post_content;

    // preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
    $excludeTemplateWords = $apidata->client->template->ex_ai_contents;
    $excludeWords = [
      $retdata->site_name,
      $retdata->slogan,
      $retdata->contact_number,
      $retdata->email_address,
      str_replace( " ", "+", $retdata->site_address ),
      $retdata->site_address,
      $retdata->insights
    ];
    $excludeWords = array_merge( $excludeWords, $excludeTemplateWords );

    $matchers = array();

    // for paragraph contents
    // match 2
    preg_match_all('/<(p)\b[^>]*>(.*?)<\/\1>/is', $content, $matchParagraphs, PREG_SET_ORDER);
    foreach( $matchParagraphs as $match ) {
      if( isset( $match[2])) {
        $countWords = count(explode(" ", $match[2]));
        $instruction = $countWords <= 5 ? "- Write a paragraph to be replaced with this content: \"{$match[2]}\", with the max word of {$countWords} words.\n" : "- Write a paragraph with the max word of {$countWords} words. No break or new line, just one-line!\n";
        $matchers[] = array(
          "instructions" => $instruction,
          "content" => $match[2]
        );
      }
    }

    // for heading tags contents
    // match 2
    preg_match_all('/<(h[2-6])\b[^>]*>(.*?)<\/\1>/is', $content, $matchesHeadings, PREG_SET_ORDER);
    foreach( $matchesHeadings as $match ) {
      if( isset( $match[2])) {
        $countWords = count(explode(" ", $match[2]));
        $matchers[] = array(
          "instructions" => "- Write a title for replacement with this content: \"{$match[2]}\", with the max word of {$countWords} words.\n",
          "content" => $match[2]
        );
      }
    }

    // for title heading attributes contents
    // match 1
    preg_match_all('/\b(title|heading)="([^"]*)"/is', $content, $matchesAttributes, PREG_SET_ORDER);
    foreach( $matchesAttributes as $match ) {
      if( isset( $match[2])) {
        $countWords = count(explode(" ", $match[2]));
        $matchers[] = array(
          "instructions" => "- Write a title for replacement with this content: \"{$match[2]}\", with the max word of {$countWords} words.\n",
          "content" => $match[2]
        );
      }
    }

    $finalizeMatchers = array();
    foreach( $matchers as $match ) {
      $matchExclude = false;

      foreach ($excludeWords as $needle) {
        if (stripos($match['content'], $needle) !== false) {
          $matchExclude = true;
          break;
        }
      }

      if( $matchExclude ) { continue; }
      $finalizeMatchers[] = $match;
    }

    if( !count( $finalizeMatchers )) { return false; }
    
    $replace_contents = array_column( $finalizeMatchers, "content" );
    $instructions = array_column( $finalizeMatchers, "instructions" );
    $instructions_text = implode("", $instructions);

    $contents = GeneratorAPI::run(
      GeneratorAPI::generatorapi( "/api/trpc/openai.askcontent" ),
      array(
        "instructions" => $instructions_text,
        "max" => count( $instructions )
      ),
      $token
    );

    $apidata = GeneratorAPI::getResponse( $contents );
    $snippetContents = $apidata->snippets;

    foreach( $replace_contents as $key => $rpcontent ) {
      if( isset( $snippetContents[$key] ) ) {
        $content = str_replace( $rpcontent, $snippetContents[$key], $content );
      }
    }

    wp_update_post([
      'ID' => $post->ID,
      'post_content' => $content
    ]);

    ET_Core_PageResource::do_remove_static_resources( $post->ID, 'all' );
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
        "project",
        "et_footer_layout",
        "et_body_layout",
      )
    ));

    foreach( $posts as $post ) {
      $this->ai_content_generate( $apidata, $token, $post);
    }
  }
}