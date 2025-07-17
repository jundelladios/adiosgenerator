<?php

namespace WebGenerator\WpCliTraits;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

use WebGenerator\GeneratorUtilities;
use WebGenerator\GeneratorAPI;
use WebGenerator\GeneratorCache;
use WP_CLI;

trait StockPhotos {

  /**
   * Reusable code to handle content replacement from AI images
   *
   * @param string $token
   * @param [type] $post
   * @return void
   */
  private function post_stockphotos( $token, $apidata, $post ) {

    $stockArgs = array();
    if( $post->post_type == "diva_services") {
      $stockArgs = array(
        "specified" => $post->post_title,
      );
    }

    $logo = get_option( GeneratorUtilities::et_adiosgenerator_option( "logo" ) );
    $logo2 = get_option( GeneratorUtilities::et_adiosgenerator_option( "logo_2" ) );
    $excludes = array(
      wp_get_attachment_url( $logo ),
      wp_get_attachment_url( $logo2 )
    );

    $excludedImageURLstemplate = $apidata->client->template->ex_ai_images;
    $replacement_domain = home_url();
    $updated_temp_exclude_urls = array_map(function($url) use ($replacement_domain) {
      return preg_replace('#https://stage-[^/]+\.adios-webgenerator\.com#', $replacement_domain, $url);
    }, $excludedImageURLstemplate);

    $excludes = array_merge( $excludes, $updated_temp_exclude_urls );
    
    $ret = array();
    $retvids = array();

    $content = $post->post_content;

    // image processing
    preg_match_all('/https?:\/\/[^"\')\s>]+\/wp-content\/[^"\')\s>]+\.(jpg|jpeg|png|webp|avif)/i', $post->post_content, $imgurls);
    foreach( $imgurls[0] as $imgurl ) {
      $urlpostid = attachment_url_to_postid( $imgurl );
      if( $urlpostid && !in_array( $imgurl, $excludes ) ) {
        $meta = wp_get_attachment_metadata( $urlpostid );
        $ret[] = array(
          "urlpostid" => $urlpostid,
          "url" => $imgurl,
          "width" =>$meta['width'] ?? 0,
          "height" => $meta['height'] ?? 0
        );
      }
    }

    // video processing
    preg_match_all('/https?:\/\/[^"\')\s>]+\/wp-content\/[^"\')\s>]+\.(mp4|mov|avi|mkv|flv|webm|wmv|3gp|ogv)/i', $post->post_content, $vidurls);
    foreach( $vidurls[0] as $vidurl ) {
      $urlpostid = attachment_url_to_postid( $vidurl );
      $retvids[] = array(
        "urlpostid" => $urlpostid,
        "url" => $vidurl
      );
    }

    // preparation for featured image
    $featuredImage = null;

    // process images
    if( count( $ret ) ) {
      $aiImagesApi = GeneratorAPI::run(
        GeneratorAPI::generatorapi( "/api/trpc/openai.askstockphotos" ),
        array_merge($stockArgs, array(
          "number_request" => count( $ret )
        )),
        $token
      );

      $aiImagesData = GeneratorAPI::getResponse( $aiImagesApi );
      if( count( $aiImagesData ) ) {
        foreach( $ret as $index => $image ) {
          if( !isset( $aiImagesData[$index] ) ) { continue; }
          $pexelsPhoto = $aiImagesData[$index]->src->original."?fit=crop&w=".$image['width']."&h=" . $image['height'];
          $pexelsUrlPath = parse_url($aiImagesData[$index]->url, PHP_URL_PATH);
          $pexelsUrlPathSegments = explode('/', trim($pexelsUrlPath, '/'));
          $lastSegment = end($pexelsUrlPathSegments);

          $photo = GeneratorUtilities::upload_file_by_url(
            $pexelsPhoto,
            sanitize_title( $aiImagesData[$index]->alt ),
            $lastSegment
          );

          if( !$photo ) { continue; }

          // featured image first photo.
          if( !$featuredImage ) {
            $featuredImage = $photo;
          }
          
          $content = str_replace(
            $image['url'],
            wp_get_attachment_url( $photo ),
            $post->post_content
          );

          wp_update_post([
            'ID' => $post->ID,
            'post_content' => $content
          ]);
        }
      }
    }

    // set featured image
    if( $featuredImage ) {
      set_post_thumbnail($post->ID, $featuredImage);
    }


    // process videos if exists
    if( count( $retvids )) {
      $aiVideosApi = GeneratorAPI::run(
        GeneratorAPI::generatorapi( "/api/trpc/openai.askvideos" ),
        array_merge($stockArgs, array(
          "number_request" => count( $retvids )
        )),
        $token
      );
      
      $aiVideosData = GeneratorAPI::getResponse( $aiVideosApi );
      if( count( $aiVideosData ) ) {
        foreach( $retvids as $index => $vid ) {
          if( !isset( $aiVideosData[$index] ) ) { continue; }
          $vidURL = "";
          foreach( $aiVideosData[$index]->video_files as $vidfile ) {
            if( $vidfile->quality == "hd" ) {
              $vidURL = $vidfile->link;
            }
          }
            
          if( empty( $vidURL ) ) { continue; }
          
          $pexelsUrlPath = parse_url($aiVideosData[$index]->url, PHP_URL_PATH);
          $pexelsUrlPathSegments = explode('/', trim($pexelsUrlPath, '/'));
          $lastSegment = end($pexelsUrlPathSegments);

          $video = GeneratorUtilities::upload_file_by_url(
            $vidURL,
            null,
            $lastSegment
          );

          if( !$video ) { continue; }
          $content = str_replace(
            $vid['url'],
            wp_get_attachment_url( $video ),
            $post->post_content
          );

          wp_update_post([
            'ID' => $post->ID,
            'post_content' => $content
          ]);
        }
      }
    }
  }


   /**
    * Replace post content and featured images into stock photos from pexels via web generator
    *
    * @param [type] $args
    * @param [type] $assoc_args
    * @return void
    */
  public function stockphotos( $args, $assoc_args ) {
    $apidata = $this->appWpTokenGet( $assoc_args );
    if( !$apidata ) return;
    
    $token = $assoc_args['token'];

    $posts = $this->get_posts_content_generate();
    foreach( $posts as $post ) {
      $this->post_stockphotos( $token, $apidata, $post );
    }

    WP_CLI::success( __( 'Stock photos has been generated. ', 'adiosgenerator' ) );
  }
}