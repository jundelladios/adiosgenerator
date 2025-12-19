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
    $this->event = $this->setEvent("ai_contents");
  }

  public function getEvent() {
    return $this->event;
  }

  /**
   * generate page and post contents from AI
   * @return void
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

    foreach ($posts as $post) {
      $this->ai_content_generate($apidata, $post);
    }

    update_option($this->getEvent(), 1);
  }

  public function allowed_replace($excludes, $paragraph) {
    if (empty($paragraph)) {
      return false;
    }
    foreach ($excludes as $word) {
      if ($paragraph !== null && $word !== null && stripos($paragraph, $word) !== false) {
        return false;
      }
    }
    return true;
  }

  private function detect_section_role($text, $tag) {
    if (in_array($tag, ['h1', 'h2', 'h3', 'h4'])) {
      return 'section heading';
    }
    if (strlen(strip_tags($text)) < 70) {
      return 'supporting text';
    }
    if (preg_match('/contact|call|quote|book/i', $text)) {
      return 'call to action';
    }
    return 'descriptive paragraph';
  }

  /**
   * Handle AI content replacement
   *
   * @param mixed $apidata
   * @param object $post
   * @return void
   */
  public function ai_content_generate($apidata, $post) {

    $retdata = $apidata->client;
    $content = $post->post_content;
    $postId  = $post->ID;

    /** ----------------------------
     * Page Intent Detection
     * ---------------------------- */
    $pageIntent = 'general marketing page';

    if (stripos($post->post_title, 'about') !== false) {
      $pageIntent = 'about page';
    } elseif (stripos($post->post_title, 'contact') !== false) {
      $pageIntent = 'contact page';
    } elseif ($post->post_type === 'diva_services') {
      $pageIntent = 'service detail page';
    }

    /** ----------------------------
     * First Heading Detection
     * ---------------------------- */
    $firstHeading = null;

    preg_match('/<(h1)\b[^>]*>(.*?)<\/h1>/is', $content, $h1match);
    if (isset($h1match[2])) {
      $firstHeading = $h1match[2];
    }

    preg_match('/\b(title|heading)="([^"]*)"/is', $content, $titlematch);
    if (!$firstHeading && isset($titlematch[2])) {
      $firstHeading = $titlematch[2];
    }

    /** ----------------------------
     * Global Site Context (CRITICAL)
     * ---------------------------- */
    $siteContext = "
This content is for a WordPress website page.

Business Name: {$retdata->site_name}
Tagline: {$retdata->slogan}
Industries: {$retdata->industries}
Business Location: {$retdata->site_address}

Page Type: {$pageIntent}
Page Title: {$post->post_title}
Primary Heading: {$firstHeading}

Rules:
- Stay strictly relevant to this page
- Do NOT introduce unrelated services or industries
- Professional, clear, client-ready tone
";

    /** ----------------------------
     * Excluded Content
     * ---------------------------- */
    $excludeTemplateWords = $retdata->template->ex_ai_contents ?? [];
    $excludeWords = array_merge([
      $retdata->site_name,
      $retdata->slogan,
      $retdata->contact_number,
      $retdata->email_address,
      $retdata->site_address,
      $retdata->insights,
      $firstHeading
    ], $excludeTemplateWords);

    $matchers = [];

    /** ----------------------------
     * Paragraphs
     * ---------------------------- */
    preg_match_all('/<(p)\b[^>]*>(.*?)<\/\1>/is', $content, $paragraphs, PREG_SET_ORDER);

    foreach ($paragraphs as $match) {
      if (!isset($match[2])) {
        continue;
      }

      if (!$this->allowed_replace($excludeWords, $match[2])) {
        continue;
      }

      $sectionRole = $this->detect_section_role($match[2], 'p');

      $matchers[] = [
        "instructions" => "
Rewrite this {$sectionRole} for a {$pageIntent}.

Original text:
\"{$match[2]}\"

Rules:
- Stay relevant to the page topic
- Improve clarity and depth
- Similar length is fine
- One paragraph only, no line breaks
",
        "content" => $match[2]
      ];
    }

    /** ----------------------------
     * Headings h2–h6
     * ---------------------------- */
    preg_match_all('/<(h[2-6])\b[^>]*>(.*?)<\/\1>/is', $content, $headings, PREG_SET_ORDER);

    foreach ($headings as $match) {
      if (!isset($match[2])) {
        continue;
      }

      if (!$this->allowed_replace($excludeWords, $match[2])) {
        continue;
      }

      $sectionRole = $this->detect_section_role($match[2], $match[1]);

      $matchers[] = [
        "instructions" => "
Rewrite this {$sectionRole} for a {$pageIntent}.

Original title:
\"{$match[2]}\"

Rules:
- Short, clear, and relevant
- Do NOT introduce new topics
- One line only
",
        "content" => $match[2]
      ];
    }

    /** ----------------------------
     * Title / Heading Attributes
     * ---------------------------- */
    preg_match_all('/\b(title|heading)="([^"]*)"/is', $content, $attributes, PREG_SET_ORDER);

    foreach ($attributes as $match) {
      if (!isset($match[2]) || $match[2] === 'false') {
        continue;
      }

      if (!$this->allowed_replace($excludeWords, $match[2])) {
        continue;
      }

      $matchers[] = [
        "instructions" => "
Rewrite this UI heading for a {$pageIntent}.

Original text:
\"{$match[2]}\"

Rules:
- Keep it short and relevant
- One line only
",
        "content" => $match[2]
      ];
    }

    if (!count($matchers)) {
      return;
    }

    /** ----------------------------
     * SAFE AI LOOP (NO BATCHING)
     * ---------------------------- */
    foreach ($matchers as $matcher) {

      $aiResponse = GeneratorAPI::run(
        GeneratorAPI::generatorapi("/api/trpc/openai.askcontent"),
        [
          "instructions" => $siteContext . "\n" . $matcher['instructions'],
          "max" => 1
        ]
      );

      $aiData = GeneratorAPI::getResponse($aiResponse);

      if (!empty($aiData->snippets[0])) {
        $content = str_replace(
          $matcher['content'],
          $aiData->snippets[0],
          $content
        );
      }
    }

    /** ----------------------------
     * Update Post & Clear Divi Cache
     * ---------------------------- */
    wp_update_post([
      'ID' => $postId,
      'post_content' => $content
    ]);

    ET_Core_PageResource::do_remove_static_resources($postId, 'all');
  }
}
