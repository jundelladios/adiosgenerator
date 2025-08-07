<?php

namespace WebGenerator\WpCliTraits;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

use WebGenerator\GeneratorUtilities;
use WP_CLI;

trait SyncApplication {

   /**
    * Pulls application data from web generator
    *
    * @param mixed $args
    * @param mixed $assoc_args
    * @return void
    */
  public function syncdata() {
    global $wpdb;
    $apidata = $this->appWpTokenGet();
    if( !$apidata ) return;

    $retdata = $apidata->client;
    $divi = (array) json_decode( json_encode($apidata->divi), true );

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
    
    WP_CLI::success( __( 'Data has been synced!', 'adiosgenerator' ) );
  }
}