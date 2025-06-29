<?php

namespace WebGenerator;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

/**
 * 
 * Class to handle contents replacements upon sync
 */
class GeneratorREST {

  public function replace_google_maps_iframe_address($content, $address) {
    return preg_replace_callback(
      '#(<iframe[^>]+src="[^"]*google\.com/maps/embed[^"]*?\?[^"]*?)q=([^"&]*)#i',
      fn($m) => $m[1] . 'q=' . urlencode($address),
      $content
    );
  }

  public function get_social_items() {
    if( class_exists( 'ET_Builder_Module_Social_Media_Follow_Item' )) {
      return wp_send_json_success((new \ET_Builder_Module_Social_Media_Follow_Item)->get_fields());
    }
    return wp_send_json_success([]);
  }

  public function authorize( \WP_REST_Request $request ) {
    $headers = $request->get_headers();
    $auth = $headers['authorization'][0] ?? '';
    preg_match('/Bearer\s+(.*)$/i', $auth, $matches);
    if(!isset( $matches[1])) {
      return null;
    }

    $token = trim($matches[1]);
    $apiParams = array(
      'headers' => array(
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'Authorization' => "Bearer $token"
      )
    );

    $request = wp_remote_post( GeneratorAPI::generatorapi( "/api/trpc/appTokens.oauthcheck" ), $apiParams);
    if( is_wp_error( $request )) {
      return false;
    }

    $body = wp_remote_retrieve_body( $request );
    $json = json_decode( $body );
    if( !$json ) { return false; }
    if( isset( $json->error ) ) { return false; }
    return true;
  }

  public function sync_data( \WP_REST_Request $request ) {
    $params = $request->get_json_params();
  }

  public function routes() {
    add_action( "rest_api_init", function() {
      register_rest_route( 'adiosgenerator', 'get_social_items', array(
        'methods' => 'GET',
        'callback' => array( $this, "get_social_items" ),
        'permission_callback' => array( $this, "authorize" )
      ));
    });
  }

}
