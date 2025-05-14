<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

/**
 * 
 * Class to handle contents replacements upon sync
 */
class AdiosGenerator_Process_Content {
  
  public static function process_shortcode( $content = "" ) {
    if( !function_exists( "et_fb_process_shortcode") ) { return null; }

    return et_fb_process_shortcode( $content );
  }

  public static function process_to_shortcode( $shortcode = array() ) {
    if( !function_exists( "et_fb_process_to_shortcode" )) { return null; }

    return et_fb_process_to_shortcode( $shortcode, array(), "", false );
  }

  public function processApi( WP_REST_Request $request ) {
    $_POST = (array) json_decode($request->get_body(), true);
    
    $postjson = get_post( $_POST["post"] );
    if( !$postjson ) { return null; }

    // update_option( 'et_adiosgenerator_options', array(
    //   'accent_color' => "#274375",
    //   'secondary_accent_color' => "#1256d0"
    // ) );

    // return get_option( "et_adiosgenerator_options", array(
    //   'accent_color' => "#222222",
    //   'secondary_accent_color' => "#222222"
    // ) );

    // $token = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWJzY3JpcHRpb25faWQiOiJzdWJfMVJLMkg1RUttWTd1ekZucEJCTHlsZXZqIiwiaWF0IjoxNzQ3MTc1NTEzLCJleHAiOjE3NDcyNjE5MTN9.R3paUYP5FoS7m9vdzrIb4hJOIPoNBNi2JiRQiI4vDV4";
    // $data = AdiosGenerator_Api::run(
    //   AdiosGenerator_Api::generatorapi( "/api/trpc/appTokens.appWpSync" ),
    //   array(
    //     "token" => $token
    //   )
    // );
    // $apidata = AdiosGenerator_Api::getResponse( $data );
    // $retdata = $apidata->client;


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

    return $postjson->post_content;

    $content = $postjson->post_content;
    return self::process_shortcode( $content );
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
