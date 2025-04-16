<?php

if ( class_exists( 'WP_CLI' ) ) {
  # wp cli to generate site application secrets
  function adiosgeneratior_wpcli_install( $args ) {
    list( $key, $subscription ) = $args;
    if( !$subscription ) {
      WP_CLI::error( "Application Subscription is required." );
    }
    if ( !$key ) {
      WP_CLI::error( "User is required." );
    }
    $user = get_user_by( "email", $key );
    if(!$user) {
      WP_CLI::error( "User not found." );
    }

    $domain = preg_replace('#^https?://#i', '', home_url());
    $appName = "Web Generator";
    $user_id = $user->ID;

    // delete previous app name application passwords
    $appPasswords = WP_Application_Passwords::get_user_application_passwords( $user_id );
    foreach( $appPasswords as $removePass ) {
      if($removePass['name'] == $appName) {
        WP_Application_Passwords::delete_application_password( $user_id, $removePass['uuid']);
      }
    }

    // create a new application password
    $created = WP_Application_Passwords::create_new_application_password( 
      $user->ID, 
      wp_slash(array( 
        'name' => $appName, 
        'app_id' => ""
      ))
    );

    $password = $created[0];
		$item     = WP_Application_Passwords::get_user_application_password( $user->ID, $created[1]['uuid'] );
    $pass = WP_Application_Passwords::chunk_password( $password );

    WP_CLI::success( "Application Password has been generated: " . $pass );

   $response = adiosgenerator_api_post_exec(
    adiosgenerator_api_url() . "/api/trpc/sites.applicationSiteConnect",
    array(
      "json" => array(
        "user_id" => $user_id,
        "user_login" => $user->user_login,
        "app_password" => $pass,
        "subscription" => $subscription
      )
    )
   );
   
   if ( !$response || isset($response->error) ) {
      WP_CLI::error( "Failed to connect your application" );
      return false;
   }

   WP_CLI::success( "Application credentials has been synced" );

    $applicationData = $response->result->data->json->form;
    $diviData = $response->result->data->json->divi;
    $wpData = $response->result->data->json->wp;

    if(!isset($applicationData->id)) {
      WP_CLI::error( "Application data is empty" );
      return false;
    }

    $faviconURL = $applicationData->favicon;
    $faviconJson = adiosgenerator_api_post_exec(
      home_url( "/wp-json/adiosgenerator/adiosgenerator_url_to_attachment" ),
      array(
        "image_url" => $faviconURL,
        "alt" => sanitize_title($applicationData->sitename . "-favicon")
      ),
      array(
        "user" => $user->user_login,
        "password" => $pass
      )
    );

    if(!$faviconJson) {
      WP_CLI::error( "Failed to upload favicon" );
    }

    $faviconID = $faviconJson->attachment_id;
    update_option( 'site_icon', $faviconID );
    WP_CLI::success( "Site Icon has been set." );

    $logoURL = $applicationData->logo;
    $logoJson = adiosgenerator_api_post_exec(
      home_url( "/wp-json/adiosgenerator/adiosgenerator_url_to_attachment" ),
      array(
        "image_url" => $logoURL,
        "alt" => sanitize_title($applicationData->sitename . "-logo")
      ),
      array(
        "user" => $user->user_login,
        "password" => $pass
      )
    );

    if(!$logoJson) {
      WP_CLI::error( "Failed to upload logo" );
    }

    $logoID = $logoJson->attachment_id;
    $logoAttachURL = wp_get_attachment_url( $logoID );

    $diviParams = (array) $diviData;
    $diviParams["divi_logo"] = $logoAttachURL;
    $diviParams["action"] = "save_epanel";

    $diviEpanelRequest = adiosgenerator_api_post_exec(
      home_url( "/wp-json/adiosgenerator/adiosgenerator_epanel_save" ),
      $diviParams,
      array(
        "user" => $user->user_login,
        "password" => $pass
      )
    );
  }

  WP_CLI::add_command( 'adiosgenerator install', 'adiosgeneratior_wpcli_install');
}