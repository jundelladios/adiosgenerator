<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

/**
 * 
 * Class to handle contents replacements upon sync
 */
class AdiosGenerator_Process_Content {
  
  public function processData( $post ) {
    
    error_log( $post->ID . " - " . $post->post_content );
  }


  // private function replace_gmap_iframe_address( $content, $address ) {
  //   return preg_replace_callback(
  //       '#<iframe[^>]+src="([^"]*google\.com/maps/embed[^"]*)"#i',
  //       function($matches) use ($address) {
  //           $src = $matches[1];
  //           $parts = parse_url($src);
  //           if (!isset($parts['query'])) return $matches[0]; // No query string
  //           parse_str($parts['query'], $query);
  //           $query['q'] = str_replace(" ", "+", $address);
  //           $new_query = http_build_query($query);
  //           $new_src = "{$parts['scheme']}://{$parts['host']}{$parts['path']}?$new_query";
  //           return str_replace($src, $new_src, $matches[0]);
  //       },
  //       $content
  //   );
  // }
  private function replace_google_maps_iframe_address($content, $address) {
      // Make sure the new address is URL-encoded
      $address = urlencode($address);
      // Regex: match Google Maps iframe and its src, target q= parameter
      return preg_replace_callback(
          '#(<iframe[^>]+src="[^"]*google\.com/maps/embed[^"]*?\?[^"]*?)q=([^"&]*)#i',
          function($matches) use ($address) {
              // $matches[1]: everything up to q=
              // $matches[2]: the old address
              return $matches[1] . 'q=' . $address;
          },
          $content
      );
  }

  public function processApi( WP_REST_Request $request ) {
    
    $_POST = (array) json_decode($request->get_body(), true);

    if( !isset( $_POST["token"] )) { return null; }

    $token = $_POST["token"];

    $data = AdiosGenerator_Api::run(
      AdiosGenerator_Api::generatorapi( "/api/trpc/appTokens.appWpSync" ),
      array(
        "token" => $token
      )
    );
    $apidata = AdiosGenerator_Api::getResponse( $data );
    $retdata = $apidata->client;
    $placeholder = $apidata->placeholder;
    $divi = (array) json_decode( json_encode($apidata->divi), true );

    $posts = get_posts(array(
      'posts_per_page' => -1,
      'post_type' => array(
        "page",
        "post",
        "project",
        "et_body_layout",
        "et_footer_layout",
        "et_header_layout",
        "et_template",
        "et_theme_builder"
      )
    ));

    $prevColors = get_option( AdiosGenerator_Utilities::et_adiosgenerator_option( "colors" ), array(
      'accent_color' => et_get_option( 'accent_color', $divi["accent_color"] ),
      'secondary_accent_color' => et_get_option( 'secondary_accent_color', $divi["secondary_accent_color"] )
    ));


    $logo = get_option( AdiosGenerator_Utilities::et_adiosgenerator_option( "logo" ) );
    $logo2 = get_option( AdiosGenerator_Utilities::et_adiosgenerator_option( "logo_2" ) );

    $contents = array();
    foreach( $posts as $pst ) {
      $content = preg_replace('/' . preg_quote($prevColors["accent_color"], '/') . '/i', $divi["accent_color"], $pst->post_content);
      $content = preg_replace('/' . preg_quote($prevColors["secondary_accent_color"], '/') . '/i', $divi["secondary_accent_color"], $content);
      $content = preg_replace('#https?://[^\s\'"]*/site-logo\.[a-zA-Z0-9]+#', wp_get_attachment_url( $logo ), $content);
      $content = preg_replace('#https?://[^\s\'"]*/site-logo-footer\.[a-zA-Z0-9]+#', wp_get_attachment_url( $logo2 ), $content);


      $content = str_replace($placeholder->contact_number, $retdata->contact_number, $content);
      $content = str_replace(
        preg_replace('/\D+/', '', $placeholder->contact_number),
        preg_replace('/\D+/', '', $retdata->contact_number),
        $content
      );

      $content = str_replace( $placeholder->site_address, $retdata->site_address, $content);
      $content = str_replace(
        urlencode( $placeholder->site_address ),
        urlencode( $retdata->site_address ),
        $content
      );

      error_log( json_encode( $placeholder ));

       wp_update_post([
        'ID' => $pst->ID,
        'post_content' => $content
      ]);

      $contents[] = $content;
    }

    do_action( "adiosgenerator_clear_cache" );

    return $contents;

    // return $this->processData( $_POST["token"] );

    // update_option( 'et_adiosgenerator_options', array(
    //   'accent_color' => "#274375",
    //   'secondary_accent_color' => "#1256d0"
    // ) );

    // return get_option( "et_adiosgenerator_options", array(
    //   'accent_color' => "#222222",
    //   'secondary_accent_color' => "#222222"
    // ) );

    // $posts_types = array(
    //   "page",
    //   "post",
    //   "project",
    //   "et_body_layout",
    //   "et_footer_layout",
    //   "et_header_layout",
    //   "et_template",
    //   "et_theme_builder"
    // );
    

    /**
     * header replacement logic
     * find the post type named et_header_layout
     * find the shortcode et_pb_menu replace logo attribute value
     */


    /**
     * footer replacement logic
     * find the post type named et_footer_layout
     * find shortcode named et_pb_image and replace the src attribute value
     */

    /**
     * replacement logic for social media networks
     * preg_replace('/\[et_pb_social_media_follow_network.*?\[\/et_pb_social_media_follow_network\]/s', 'hello world', $input_lines);
     * 
     * 
     */

     /**
      * logic to replace social media follow content from the previous logic
      */
    // $content = preg_replace_callback(
    //     '/\[et_pb_social_media_follow([^\]]*)\](.*?)\[\/et_pb_social_media_follow\]/s',
    //     function ($matches) {
    //         $attributes = $matches[1]; // Keeps the original attributes
    //         $newContent = 'YOUR_NEW_CHILD_CONTENT_HERE'; // Replace this with your desired content
    //         return "[et_pb_social_media_follow{$attributes}]{$newContent}[/et_pb_social_media_follow]";
    //     },
    //     $content
    // );

    // return $retdata;
    
    
    // return $postjson->post_content;

    // $content = $postjson->post_content;
    // return self::process_shortcode( $content );
  }

  public function get_social_items() {
    if( class_exists( 'ET_Builder_Module_Social_Media_Follow_Item' )) {
      return wp_send_json_success((new ET_Builder_Module_Social_Media_Follow_Item)->get_fields());
    }
    return wp_send_json_success([]);
  }

  public function routes() {
    add_action( "rest_api_init", function() {
      register_rest_route( 'adiosgenerator', 'process-content', array(
        'methods' => 'POST',
        'callback' => array( $this, "processApi" ),
        'permission_callback' => function() {
          return "__false";
        }
      ));

      register_rest_route( 'adiosgenerator', 'social-lists', array(
        'methods' => 'GET',
        'callback' => array( $this, "get_social_items" ),
        'permission_callback' => function() {
          return "__false";
        }
      ));
    });

  }
}

(new AdiosGenerator_Process_Content)->routes();
