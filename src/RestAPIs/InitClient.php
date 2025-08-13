<?php

namespace WebGenerator\RestAPIs;

use WebGenerator\GeneratorREST;
use WebGenerator\GeneratorAPI;
use WebGenerator\GeneratorUtilities;

class InitClient extends GeneratorREST {

  public function route() {
    add_action( "rest_api_init", function() {
      register_rest_route( 'adiosgenerator', 'client/init', array(
        'methods' => 'POST',
        'callback' => array( $this, "load" ),
        'permission_callback' => array( $this, 'authorize' )
      ));
    });
  }

  public function load() {
    $data = GeneratorAPI::run(
      GeneratorAPI::generatorapi( "/api/trpc/appTokens.appWpSync" ),
      array()
    );
    $apidata = GeneratorAPI::getResponse( $data );
    if(!isset( $apidata->client )) {
      return wp_send_json_error( array(
        'message' => "Failed to load your client data from API"
      ));
    }

    update_option( GeneratorUtilities::et_adiosgenerator_option( 'client_data' ), json_encode( $apidata ) );
    return wp_send_json_success( array(
      'message' => "App client has been initialized"
    ));
  }
}
