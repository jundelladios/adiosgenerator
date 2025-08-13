<?php

namespace WebGenerator\RestAPIs;

use WebGenerator\GeneratorREST;

class SocialMediaItems extends GeneratorREST {

  public function route() {
    add_action( "rest_api_init", function() {
      register_rest_route( 'adiosgenerator', 'get_social_items', array(
        'methods' => 'GET',
        'callback' => array( $this, "load" ),
        'permission_callback' => array( $this, 'authorize' )
      ));
    });
  }

  public function load() {
    if( class_exists( 'ET_Builder_Module_Social_Media_Follow_Item' )) {
      return wp_send_json_success((new \ET_Builder_Module_Social_Media_Follow_Item)->get_fields());
    }
    return wp_send_json_success([]);
  }
}
