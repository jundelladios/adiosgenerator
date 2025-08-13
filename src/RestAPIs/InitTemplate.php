<?php

namespace WebGenerator\RestAPIs;

use WebGenerator\GeneratorREST;
use WebGenerator\GeneratorAPI;
use WebGenerator\GeneratorUtilities;

class InitTemplate extends GeneratorREST {

  public function route() {
    add_action( "rest_api_init", function() {
      register_rest_route( 'adiosgenerator', 'template/init', array(
        'methods' => 'POST',
        'callback' => array( $this, "load" ),
        'permission_callback' => array( $this, 'authorize' )
      ));
    });
  }

  public function load() {
    $data = GeneratorAPI::run(
      GeneratorAPI::generatorapi( "/api/trpc/appTokens.appWpTemplateSync" ),
      array()
    );
    $apidata = GeneratorAPI::getResponse( $data );
    if(!isset( $apidata->template )) {
      return wp_send_json_error( array(
        'message' => "Failed to load your template data from API"
      ));
    }

    update_option( GeneratorUtilities::et_adiosgenerator_option( 'template_data' ), json_encode( $apidata ) );
    return wp_send_json_success( array(
      'message' => "App template has been initialized"
    ));
  }
}
