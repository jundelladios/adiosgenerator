<?php

namespace WebGenerator\RestAPIs;

use WebGenerator\GeneratorREST;
use WebGenerator\GeneratorUtilities;

class SyncClient extends GeneratorREST {

  public function route() {
    add_action( "rest_api_init", function() {
      register_rest_route( 'adiosgenerator', 'client/sync', array(
        'methods' => 'POST',
        'callback' => array( $this, "load" ),
        'permission_callback' => array( $this, 'authorize' )
      ));
    });
  }

  public function load() {
    global $wpdb;
    $apidata = $this->get_client();

    $retdata = $apidata->client;
    $divi = (array) $apidata->divi;

    // sync data indicates that this is client application
    update_option( GeneratorUtilities::et_adiosgenerator_option( "client_application" ), 1 );
    
    /**
     * Store Previews Theme Accent Colors
     * ensure to not override existing option
     */
    $et_adios_options = get_option( GeneratorUtilities::et_adiosgenerator_option( "colors" ), [] );
    if( function_exists( 'et_get_option' ) && !isset( $et_adios_options["accent_color"] ) ) {
      // ensure to not override existing option
      update_option( GeneratorUtilities::et_adiosgenerator_option( "colors" ), array(
        'accent_color' => et_get_option( 'accent_color', $divi["accent_color"] ),
        'secondary_accent_color' => et_get_option( 'secondary_accent_color', $divi["secondary_accent_color"] )
      ) );
    }

    /**
     * Elegant themes options
     */
     if( function_exists( 'et_update_option' ) ) {
      foreach ( $divi as $key => $option ) {
        et_update_option( $key, $option );
      }
    }
    if( isset( $divi['background_color'] ) ) {
      set_theme_mod( 'background_color', "" );
    }


    /**
     * admin email
     */
    if(!email_exists( $retdata->email_address )) {
       $user_id = wp_create_user(
        $retdata->email_address, 
        wp_generate_password(20, true, true),
        $retdata->email_address
      );
    }

    $wpdb->update(
      $wpdb->options,
      array('option_value' => $retdata->email_address), // Data to update
      array('option_name'  => 'admin_email') // Where clause
    );

    // Also remove any pending change
    $wpdb->delete(
      $wpdb->options,
      array('option_name' => 'new_admin_email')
    );
    
    return wp_send_json_success( array(
      'message' => "App client has been synced"
    ));
  }
}
