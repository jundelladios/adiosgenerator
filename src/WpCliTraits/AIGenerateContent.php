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

  private function allowed_replace( $excludes, $paragraph ) {
    if( empty( $paragraph )) { return false; }
    foreach ($excludes as $word) {
      if (stripos($paragraph, $word) !== false) {
        return false;
      }
    }
    return true;
  }

  private function ai_content_generate( $apidata, $post ) {

    $retdata = $apidata->client;
    $content = $post->post_content;
    $postId = $post->ID;

    $firstHeading = null;

    preg_match('/<(h1)\b[^>]*>(.*?)<\/h1>/is', $content, $h1match);
    if( isset( $h1match[2] ) ) {
      $firstHeading = $h1match[2];
    }

    preg_match('/\b(title|heading)="([^"]*)"/is', $content, $titleheadmatch);
    if( isset( $titleheadmatch[2] ) && !$firstHeading ) {
      $firstHeading = $titleheadmatch[2];
    }

    $excludeTemplateWords = $apidata->client->template->ex_ai_contents;
    $excludeWords = [
      $retdata->site_name,
      $retdata->slogan,
      $retdata->contact_number,
      $retdata->email_address,
      str_replace( " ", "+", $retdata->site_address ),
      $retdata->site_address,
      $retdata->insights,
      $firstHeading
    ];

    $excludeWords = array_merge( $excludeWords, $excludeTemplateWords );

    $matchers = array();
    $appendTitle =" for this page ({$post->post_title})";

    /**
     * Paragraphs
     */
    preg_match_all('/<(p)\b[^>]*>(.*?)<\/\1>/is', $content, $matchParagraphs, PREG_SET_ORDER);
    foreach( $matchParagraphs as $match ) {
      if( isset( $match[2])) {

        $countWords = count( explode( " ", trim( strip_tags( $match[2] ) ) ) );

        $instruction = $countWords <= 5
          ? "- Rewrite this text{$appendTitle} keeping the SAME meaning and topic: \"{$match[2]}\". It must be strictly related to these industries: {{{industries}}}. Do NOT introduce new services, ideas, or information. Max {$countWords} words.\n"
          : "- Rewrite this text{$appendTitle} keeping the SAME meaning and topic. It must be strictly related to these industries: {{{industries}}}. Do NOT introduce new services, ideas, or information. Max {$countWords} words. One line only.\n";

        $matchers[] = array(
          "instructions" => $instruction,
          "content" => $match[2]
        );
      }
    }

    /**
     * Headings
     */
    preg_match_all('/<(h[2-6])\b[^>]*>(.*?)<\/\1>/is', $content, $matchesHeadings, PREG_SET_ORDER);
    foreach( $matchesHeadings as $match ) {
      if( isset( $match[2])) {

        $countWords = count( explode( " ", trim( strip_tags( $match[2] ) ) ) );

        $matchers[] = array(
          "instructions" =>
            "- Rewrite this title{$appendTitle} keeping the SAME topic and intent: \"{$match[2]}\". It must be strictly related to these industries: {{{industries}}}. Do NOT add new ideas or topics. Max {$countWords} words. One line only.\n",
          "content" => $match[2]
        );
      }
    }

    /**
     * Title / heading attributes
     */
    preg_match_all('/\b(title|heading)="([^"]*)"/is', $content, $matchesAttributes, PREG_SET_ORDER);
    foreach( $matchesAttributes as $match ) {
      if( isset( $match[2]) && $match[2] !== "false") {

        $countWords = count( explode( " ", trim( strip_tags( $match[2] ) ) ) );

        $matchers[] = array(
          "instructions" =>
            "- Rewrite this title{$appendTitle} keeping the SAME topic: \"{$match[2]}\". It must be strictly related to these industries: {{{industries}}}. Do NOT introduce new ideas. Max {$countWords} words. One line only.\n",
          "content" => $match[2]
        );
      }
    }

    if( !count( $matchers )) { return false; }

    $finalMatchers = array();
    foreach( $matchers as $matcher ) {
      if( $this->allowed_replace( $excludeWords, $matcher['content'] ) ) {
        $finalMatchers[] = $matcher;
      }
    }

    $replace_contents = array_column( $finalMatchers, "content" );
    $instructions     = array_column( $finalMatchers, "instructions" );

    /**
     * 🔴 CRITICAL: Instruction Guard (RELEVANCE FIX)
     */
    $instructions_text =
"CRITICAL RULES:
You are ONLY allowed to rewrite the provided text.
You MUST preserve the original topic, intent, and meaning.
You MUST NOT add new ideas, services, features, or information.
You MUST respect the maximum word limits exactly.
If unsure, rewrite conservatively and minimally.

" . implode("", $instructions);

    $aiContentsApi = GeneratorAPI::run(
      GeneratorAPI::generatorapi( "/api/trpc/openai.askcontent" ),
      array(
        "instructions" => $instructions_text,
        "max" => count( $instructions )
      )
    );

    $apidata = GeneratorAPI::getResponse( $aiContentsApi );
    $snippetContents = $apidata->snippets;

    foreach( $replace_contents as $key => $rpcontent ) {
      if( isset( $snippetContents[$key] ) ) {
        $content = str_replace( $rpcontent, $snippetContents[$key], $content );
      }
    }

    wp_update_post([
      'ID' => $postId,
      'post_content' => $content
    ]);

    ET_Core_PageResource::do_remove_static_resources( $postId, 'all' );
    ET_Core_PageResource::do_remove_static_resources( 'all', 'all' );
  }

  public function generate_ai_content() {
    $apidata = $this->appWpTokenGet();
    if( !$apidata ) return;

    $posts = get_posts(array(
      'posts_per_page' => -1,
      'post_type' => array(
        "page",
        "post",
        "project",
        "diva_services",
        "et_footer_layout",
        "et_body_layout"
      )
    ));

    foreach( $posts as $post ) {
      $this->ai_content_generate( $apidata, $post);
    }

    WP_CLI::success( __( 'AI contents has been generated.', 'adiosgenerator' ) );
  }
}
