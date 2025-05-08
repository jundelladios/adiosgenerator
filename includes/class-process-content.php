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

    return $postjson->post_content;

    $content = $postjson->post_content;
    return self::process_shortcode( $content );
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
    });
  }
}

// (new AdiosGenerator_Process_Content)->routes();
