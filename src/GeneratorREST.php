<?php

namespace WebGenerator;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

use WebGenerator\GeneratorUtilities;
use WebGenerator\GeneratorAPI;
use WebGenerator\GeneratorProcessContent;

class GeneratorREST {

  /**
   * Authorize API if key is valid in web portal
   *
   * @param \WP_REST_Request $request
   * @return void
   */
  public function authorize( \WP_REST_Request $request ) {
    $headers = $request->get_headers();
    $auth = $headers['authorization'][0] ?? '';
    preg_match('/Bearer\s+(.*)$/i', $auth, $matches);
    if(!isset( $matches[1])) {
      return null;
    }

    $token = trim($matches[1]);
    $apiParams = array(
      'timeout' => 86400,
      'headers' => array(
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'Authorization' => "Bearer $token",
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Pragma'        => 'no-cache',
        'Expires'       => '0',
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


  /**
   * Get divi social media items lists
   *
   * @return void
   */
  public function get_social_items() {
    if( class_exists( 'ET_Builder_Module_Social_Media_Follow_Item' )) {
      return wp_send_json_success((new \ET_Builder_Module_Social_Media_Follow_Item)->get_fields());
    }
    return wp_send_json_success([]);
  }

  /**
   * Initialize api routes
   *
   * @return void
   */
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
