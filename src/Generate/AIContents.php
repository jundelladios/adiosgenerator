<?php

namespace WebGenerator\Generate;

use WebGenerator\RestAPIs\Generate;
use WebGenerator\GeneratorAPI;
use WebGenerator\GeneratorUtilities;
use WebGenerator\GeneratorDiviLoader;
use ET_Core_PageResource;

class AIContents extends Generate {

  private $event;

  public function __construct() {
    $this->event = $this->setEvent( "ai_contents" );
  }

  public function getEvent() {
    return $this->event;
  }

  /**
   * generate page and post contents from AI
   */
  public function execute() {
    GeneratorDiviLoader::ensure_divi_classes_loaded();
    
    $apidata = $this->get_client();
    $posts = get_posts([
      'posts_per_page' => -1,
      'post_type' => [
        "page",
        "post",
        "project",
        "diva_services",
        "et_footer_layout",
        "et_body_layout"
      ]
    ]);

    foreach ( $posts as $post ) {
      $this->ai_content_generate( $apidata, $post );
    }

    update_option( $this->getEvent(), 1 );
  }

  public function allowed_replace( $excludes, $paragraph ) {
    if ( empty( $paragraph ) ) return false;
    foreach ( $excludes as $word ) {
      if ( $paragraph !== null && $word !== null && stripos( $paragraph, $word ) !== false ) {
        return false;
      }
    }
    return true;
  }

  /**
   * Reusable code to handle content replacement from AI
   */
  public function ai_content_generate( $apidata, $post ) {

    $retdata = $apidata->client;
    $content = $post->post_content;
    $postId  = $post->ID;

    $firstHeading = null;

    preg_match('/<(h1)\b[^>]*>(.*?)<\/h1>/is', $content, $h1match);
    if ( isset( $h1match[2] ) ) {
      $firstHeading = $h1match[2];
    }

    preg_match('/\b(title|heading)="([^"]*)"/is', $content, $titleheadmatch);
    if ( isset( $titleheadmatch[2] ) && !$firstHeading ) {
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

    $matchers = [];
    $appendTitle = " for this page ({$post->post_title})";

    /**
     * Paragraphs
     */
    preg_match_all('/<(p)\b[^>]*>(.*?)<\/\1>/is', $content, $matchParagraphs, PREG_SET_ORDER);
    foreach ( $matchParagraphs as $match ) {
      if ( isset( $match[2] ) ) {

        $instruction =
"- Rewrite this text{$appendTitle} keeping the SAME meaning and topic:
\"{$match[2]}\"

Rules:
- Strictly related to these industries: {{{industries}}}
- Do NOT introduce new ideas, services, or information
- Keep the same length as the original
- One line only
";

        $matchers[] = [
          "instructions" => $instruction,
          "content" => $match[2]
        ];
      }
    }

    /**
     * Headings
     */
    preg_match_all('/<(h[2-6])\b[^>]*>(.*?)<\/\1>/is', $content, $matchesHeadings, PREG_SET_ORDER);
    foreach ( $matchesHeadings as $match ) {
      if ( isset( $match[2] ) ) {

        $matchers[] = [
          "instructions" =>
"- Rewrite this title{$appendTitle} keeping the SAME topic and intent:
\"{$match[2]}\"

Rules:
- Strictly related to these industries: {{{industries}}}
- Do NOT add new ideas or topics
- Keep the same length
- One line only
",
          "content" => $match[2]
        ];
      }
    }

    /**
     * Title / heading attributes
     */
    preg_match_all('/\b(title|heading)="([^"]*)"/is', $content, $matchesAttributes, PREG_SET_ORDER);
    foreach ( $matchesAttributes as $match ) {
      if ( isset( $match[2] ) && $match[2] !== "false" ) {

        $matchers[] = [
          "instructions" =>
"- Rewrite this title{$appendTitle} keeping the SAME topic:
\"{$match[2]}\"

Rules:
- Strictly related to these industries: {{{industries}}}
- Do NOT introduce new ideas
- Keep the same length
- One line only
",
          "content" => $match[2]
        ];
      }
    }

    if ( ! count( $matchers ) ) {
      return false;
    }

    $finalMatchers = [];
    foreach ( $matchers as $matcher ) {
      if ( $this->allowed_replace( $excludeWords, $matcher['content'] ?? "" ) ) {
        $finalMatchers[] = $matcher;
      }
    }

    $replace_contents = array_column( $finalMatchers, "content" );
    $instructions     = array_column( $finalMatchers, "instructions" );

    /**
     * 🔴 CRITICAL INSTRUCTION GUARD (RELEVANCE FIX)
     */
    $instructions_text =
"CRITICAL RULES:
You are ONLY allowed to rewrite the provided text.
You MUST preserve the original topic, intent, and meaning.
You MUST NOT add new ideas, services, features, or information.
You MUST keep the same length and structure.
If unsure, rewrite conservatively and minimally.

" . implode("", $instructions);

    $aiContentsApi = GeneratorAPI::run(
      GeneratorAPI::generatorapi( "/api/trpc/openai.askcontent" ),
      [
        "instructions" => $instructions_text,
        "max" => count( $instructions )
      ]
    );

    $apidata = GeneratorAPI::getResponse( $aiContentsApi );
    $snippetContents = $apidata->snippets;

    foreach ( $replace_contents as $key => $rpcontent ) {
      if ( isset( $snippetContents[$key] ) ) {
        $content = str_replace( $rpcontent, $snippetContents[$key], $content );
      }
    }

    wp_update_post([
      'ID' => $postId,
      'post_content' => $content
    ]);

    ET_Core_PageResource::do_remove_static_resources( $postId, 'all' );
  }
}
