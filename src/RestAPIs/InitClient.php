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

    // Add WP-CLI command for client init
    if ( defined('WP_CLI') && WP_CLI ) {
      \WP_CLI::add_command('adiosgenerator client-init', function() {
        $instance = new \WebGenerator\RestAPIs\InitClient();
        $result = $instance->load();
        if ( is_wp_error($result) ) {
          \WP_CLI::error( $result->get_error_message() );
        } elseif ( is_array($result) && isset($result['success']) && !$result['success'] ) {
          \WP_CLI::error( isset($result['data']['message']) ? $result['data']['message'] : 'Client init failed.' );
        } else {
          \WP_CLI::success('App client has been initialized');
        }
      });
    }
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
