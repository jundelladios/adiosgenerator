<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

/**
 * 
 * Class to handle contents replacements upon sync
 */
class AdiosGenerator_Process_Content {

  public function replace_google_maps_iframe_address($content, $address) {
    return preg_replace_callback(
      '#(<iframe[^>]+src="[^"]*google\.com/maps/embed[^"]*?\?[^"]*?)q=([^"&]*)#i',
      fn($m) => $m[1] . 'q=' . urlencode($address),
      $content
    );
  }

  public function processApi( WP_REST_Request $request ) {
    return true;
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
