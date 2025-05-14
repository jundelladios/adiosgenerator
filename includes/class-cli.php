<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * 
 * WP Cli commands for adiosgenerator
 */
class AdiosGenerator_WPCli extends WP_CLI_Command {
  
  /**
   * Clear Divi static resources and all caches
   */
  public function clear() {
    if(class_exists("ET_Core_PageResource")) {
      ET_Core_PageResource::remove_static_resources( 'all', 'all', true );
      WP_CLI::success( __( 'Divi static css files has been cleared!', 'adiosgenerator' ) );
    }
    wp_cache_flush();
    WP_CLI::success( __( 'All cache has been cleared!', 'adiosgenerator' ) );
  }


  /**
   * Pulls data and sync presets and content from generator
   */
  public function syncdata( $args, $assoc_args ) {
    if( !isset( $assoc_args['token'] ) ) {
      WP_CLI::error( __( 'You need to specify the --token=<token> parameter', 'adiosgenerator' ) );
    }

    $token = $assoc_args['token'];
    $data = AdiosGenerator_Api::run(
      AdiosGenerator_Api::generatorapi( "/api/trpc/appTokens.appWpSync" ),
      array(
        "token" => $token
      )
    );

    $apidata = AdiosGenerator_Api::getResponse( $data );
    if(!$apidata) {
      WP_CLI::error( __( 'Failed to load your data. App token is invalid!', 'adiosgenerator' ) );
      return false;
    }

    AdiosGenerator_Utilities::disable_post_revision();

    $retdata = $apidata->client;
    $divi = (array) json_decode( json_encode($apidata->divi), true );

    $logo = AdiosGenerator_Utilities::upload_file_by_url(
      $retdata->logo,
      sanitize_title( $retdata->site_name . "-logo" )
    );
    $logourl = wp_get_attachment_url( $logo );

    
    $favicon = AdiosGenerator_Utilities::upload_file_by_url(
      $retdata->favicon,
      sanitize_title( $retdata->site_name . "-favicon" )
    );
    update_option( 'site_icon', $favicon );


    /**
     * Store Previews Theme Accent Colors
     */
    if( function_exists( 'et_get_option' )) {
      update_option( 'et_adiosgenerator_options', array(
        'accent_color' => et_get_option( 'accent_color', $divi["accent_color"] ),
        'secondary_accent_color' => et_get_option( 'secondary_accent_color', $divi["secondary_accent_color"] )
      ) );
    }


    /**
     * Elegant themes options
     */
     if( function_exists( 'et_update_option' ) ) {
      $divi["divi_logo"] = $logourl;
      foreach ( $divi as $key => $option ) {
        et_update_option( $key, $option );
      }
    }

    $this->clear();
    WP_CLI::success( __( 'Data has been synced!', 'adiosgenerator' ) );
  }



  /**
   * Pulls data sync for templates
   */
  public function sync_template_data( $args, $assoc_args ) {
    if( !isset( $assoc_args['token'] ) ) {
      WP_CLI::error( __( 'You need to specify the --token=<token> parameter', 'adiosgenerator' ) );
    }

    $token = $assoc_args['token'];
    $data = AdiosGenerator_Api::run(
      AdiosGenerator_Api::generatorapi( "/api/trpc/appTokens.appWpTemplateSync" ),
      array(
        "token" => $token
      )
    );

    $apidata = AdiosGenerator_Api::getResponse( $data );
    
    if(!$apidata) {
      WP_CLI::error( __( 'Failed to load your data. App token is invalid!', 'adiosgenerator' ) );
      return false;
    }

    AdiosGenerator_Utilities::disable_post_revision();
    $divi = (array) json_decode( json_encode($apidata->divi), true );

    /**
     * Elegant themes options
     */
     if( function_exists( 'et_update_option' ) ) {
      foreach ( $divi as $key => $option ) {
        et_update_option( $key, $option );
      }
    }

    $this->clear();
    WP_CLI::success( __( 'Data has been synced!', 'adiosgenerator' ) );
  }

}

WP_CLI::add_command(
  'adiosgenerator',
  'AdiosGenerator_WPCli'
);