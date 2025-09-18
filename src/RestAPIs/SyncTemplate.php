<?php

namespace WebGenerator\RestAPIs;

use WebGenerator\GeneratorREST;

class SyncTemplate extends GeneratorREST {

  public function route() {
    add_action( "rest_api_init", function() {
      register_rest_route( 'adiosgenerator', 'template/sync', array(
        'methods' => 'POST',
        'callback' => array( $this, "load" ),
        'permission_callback' => array( $this, 'authorize' )
      ));
    });

    // Add WP-CLI command for template sync
    if ( defined('WP_CLI') && WP_CLI ) {
      \WP_CLI::add_command('adiosgenerator template-sync', function() {
        $instance = new \WebGenerator\RestAPIs\SyncTemplate();
        $instance->load();
      });
    }
  }

  public function load() {
    $apidata = $this->get_template();
    $retdata = $apidata->client;
    $divi = (array) $apidata->divi;

    /**
     * Elegant themes options
     */
     if( function_exists( 'et_update_option' ) ) {
      foreach ( $divi as $key => $option ) {
        et_update_option( $key, $option );
      }
    }

    return wp_send_json_success( array(
      'message' => "App template has been synced"
    ));
  }
}
