<?php

use \Divi\Onboarding\Helpers;

if ( class_exists( 'WP_CLI' ) ) {
  
  # wp cli for breeze imports
  function adiosgenerator_wpcli_breeze_import_defaults( $args, $assoc_args ) {
		if ( empty( $assoc_args ) || ! isset( $assoc_args['file-path'] ) ) {
			WP_CLI::error(
				__( 'You need to specify the --file-path=<full_path_to_file> parameter', 'breeze' )
			);

			return;
		}

		$file_path = trim( $assoc_args['file-path'] );

		if ( empty( $file_path ) ) {
			WP_CLI::error(
				__( 'You need to specify the full url to breeze JSON file', 'breeze' )
			);

			return;
		}
    

		$json = json_decode( file_get_contents( $file_path), true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			WP_CLI::error(
				sprintf(
				/* translators: %s The JSON had an issue */
					__( 'There was an error running the action scheduler: %s', 'breeze' ),
					json_last_error_msg()
				)
			);

			return;
		}

		if (
			isset( $json['breeze_basic_settings'] ) &&
			isset( $json['breeze_advanced_settings'] ) &&
			isset( $json['breeze_cdn_integration'] )
		) {
			WP_CLI::success(
				__( 'The provided JSON is valid...importing data', 'breeze' )
			);

			$level = '';
			if ( ! empty( $assoc_args ) && isset( $assoc_args['level'] ) && ! empty( trim( $assoc_args['level'] ) ) ) {
				if ( 'network' === trim( $assoc_args['level'] ) || is_numeric( $assoc_args['level'] ) ) {

					if ( is_string( $assoc_args['level'] ) && ! is_numeric( $assoc_args['level'] ) ) {
						$level = trim( $assoc_args['level'] );

					} elseif ( is_numeric( trim( $assoc_args['level'] ) ) ) {
						$level   = absint( trim( $assoc_args['level'] ) );
						$is_blog = get_blog_details( $level );

						if ( empty( $is_blog ) ) {
							WP_CLI::error(
								__( 'The blog ID is not valid, --level=<blog_id>', 'breeze' )
							);

							return;
						}
					}
				} else {
					WP_CLI::error(
						__( 'Parameter --level=<network|blog_id> does not contain valid data', 'breeze' )
					);
				}
			}
			if ( ! isset( $json['breeze_file_settings'] ) && ! isset( $json['breeze_preload_settings'] ) ) {
				$settings_action = Breeze_Settings_Import_Export::replace_options_old_to_new( $json, $level, true );
			} else {
				$settings_action = Breeze_Settings_Import_Export::replace_options_cli( $json, $level );
			}

			if ( true === $settings_action ) {
				WP_CLI::success(
					__( 'Settings have been imported', 'breeze' )
				);
			} else {
				WP_CLI::error(
					__( 'Error improting the settings, check the JSON file', 'breeze' ) . ' : ' . $file_path
				);
			}
		} else {
			WP_CLI::error(
				__( 'The JSON file does not contain valid data', 'breeze' ) . ' : ' . $file_path
			);
		}

		WP_CLI::line( WP_CLI::colorize( '%YDone%n.' ) );

	}

  WP_CLI::add_command( 'adiosgenerator breeze_import', 'adiosgenerator_wpcli_breeze_import_defaults');





  # wp cli to generate site application secrets
  function adiosgenerator_wpcli_install( $args ) {
    list( $key, $subscription ) = $args;
    if( !$subscription ) {
      WP_CLI::error( 
        __( 'Application Subscription is required.', 'adiosgenerator' )
      );
    }
    if ( !$key ) {
      WP_CLI::error( 
        __( 'User is required.', 'adiosgenerator' ) 
      );
    }
    $user = get_user_by( "email", $key );
    if(!$user) {
      WP_CLI::error( 
        __( 'User not found.', 'adiosgenerator' )  
      );
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

    WP_CLI::success( 
      sprintf(
        __( 'Application Password has been generated: %s', 'adiosgenerator' ),
        $pass
      ) 
    );

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
      WP_CLI::error( 
        __( 'Failed to connect your application', 'adiosgenerator' )
      );
      return false;
   }

   WP_CLI::success( 
    __( 'Application credentials has been synced', 'adiosgenerator' ) 
  );

    $applicationData = $response->result->data->json->form;
    $diviData = $response->result->data->json->divi;

    if(!isset($applicationData->id)) {
      WP_CLI::error( 
        __( 'Application data is empty', 'adiosgenerator' )  
      );
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
      WP_CLI::error( 
        __( 'Failed to upload favicon', 'adiosgenerator' ) 
      );
    }

    $faviconID = $faviconJson->attachment_id;
    update_option( 'site_icon', $faviconID );
    WP_CLI::success( 
      __( 'Site Icon has been set.', 'adiosgenerator' ) 
    );

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
      WP_CLI::error( 
        __( 'Failed to upload logo', 'adiosgenerator' ) 
      );
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

    WP_CLI::success( 
      __( 'Divi options and performance has been set', 'adiosgenerator' )  
    );

    $site_info = Helpers\get_site_info();
    Helpers\update_site_info( 
      $logoAttachURL, 
      $applicationData->sitename, 
      $applicationData->slogan, 
      $applicationData->insights 
    );

    WP_CLI::success( 
      __( 'Site information has been updated.', 'adiosgenerator' )  
    );

    $diviLayoutDefaults = array(
      "et_ai_layout_heading_font" => $applicationData->headerFont,
      "et_ai_layout_body_font" => $applicationData->bodyFont,
      "et_ai_layout_primary_color" => $applicationData->primaryColor,
      "et_ai_layout_secondary_color" => $applicationData->secondaryColor,
      "site_description" => $applicationData->metaDescription
    );

    foreach ( $diviLayoutDefaults as $key => $option ) {
			et_update_option( $key, $option );
		}

    WP_CLI::success( 
      __( 'Divi Layout Defaults has been saved.', 'adiosgenerator' )  
    );
    
    
    
    // WP_CLI::runcommand( 'adiosgenerator breeze_import --file-path=' . adiosgenerator_api_url() . "/json/breeze.json" );

  }
  WP_CLI::add_command( 'adiosgenerator install', 'adiosgenerator_wpcli_install');
}