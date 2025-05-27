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
    AdiosGenerator_Cache::clear_cache();
    WP_CLI::success( __( 'All cache has been cleared!', 'adiosgenerator' ) );
  }

  /**
   * Pulls data and sync presets and content from generator
   */
  public function syncdata( $args, $assoc_args ) {
    global $wpdb;
    $apidata = $this->appWpTokenGet( $assoc_args );
    if( !$apidata ) return;

    $retdata = $apidata->client;
    $divi = (array) json_decode( json_encode($apidata->divi), true );
    
    /**
     * Store Previews Theme Accent Colors
     * ensure to not override existing option
     */
    $et_adios_options = get_option( AdiosGenerator_Utilities::et_adiosgenerator_option( "colors" ), [] );
    if( function_exists( 'et_get_option' ) && !isset( $et_adios_options["accent_color"] ) ) {
      // ensure to not override existing option
      update_option( AdiosGenerator_Utilities::et_adiosgenerator_option( "colors" ), array(
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



  /**
   * Pulls data sync for templates
   */
  public function sync_template_data( $args, $assoc_args ) {
    $apidata = $this->appWpTokenGet( $assoc_args, "appWpTemplateSync" );
    if( !$apidata ) return;
    $retdata = $apidata->client;
    $divi = (array) json_decode( json_encode($apidata->divi), true );

    /**
     * Elegant themes options
     */
     if( function_exists( 'et_update_option' ) ) {
      foreach ( $divi as $key => $option ) {
        et_update_option( $key, $option );
      }
    }

    WP_CLI::success( __( 'Data has been synced!', 'adiosgenerator' ) );
  }

  private function appWpTokenGet( $assoc_args, $endpoint="appWpSync" ) {
    if( !isset( $assoc_args['token'] ) ) {
      WP_CLI::error( __( 'You need to specify the --token=<token> parameter', 'adiosgenerator' ) );
      return false;
    }
    $token = $assoc_args['token'];
    $data = AdiosGenerator_Api::run(
      AdiosGenerator_Api::generatorapi( "/api/trpc/appTokens.{$endpoint}" ),
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
    return $apidata;
  }

  public function site_logo( $args, $assoc_args ) {
    $apidata = $this->appWpTokenGet( $assoc_args );
    if( !$apidata ) return;

    $retdata = $apidata->client;
    $divi = (array) json_decode( json_encode($apidata->divi), true );

    $logoparams = array(
      "text" => ucwords($retdata->site_name),
      "width" => 400,
      "height" => 70,
      "background" => implode(",", AdiosGenerator_Utilities::hexToRgba( $divi["accent_color"] )),
      "color" => implode(",", AdiosGenerator_Utilities::hexToRgba( "#FFFFFF" )),
      "fontSize" => 25,
      "font" => $retdata->header_font,
      "format" => ".png"
    );
    $logoparams = http_build_query( $logoparams );
    $mainLogoPlaceholder = ADIOSGENERATOR_LOGOMAKER_URL . "/logomaker/logo?{$logoparams}";
    $thelogo = $retdata->logo ? $retdata->logo : $mainLogoPlaceholder;
    $logo = AdiosGenerator_Utilities::upload_file_by_url(
      $thelogo,
      sanitize_title( $retdata->site_name . "-logo" ),
      sanitize_title( $retdata->site_name . "-logo" )
    );
    
    if( $logo ) {
      update_option( AdiosGenerator_Utilities::et_adiosgenerator_option("logo"), $logo );
      if( function_exists( 'et_update_option') ) {
        et_update_option( "divi_logo", wp_get_attachment_url( $logo ) );
      }
      // disable lazyload and lcp high prio
      update_post_meta( $logo, "adiosgenerator_disable_lazyload", 1 );
      update_post_meta( $logo, "adiosgenerator_prioritize_background", 5 );

      // remove previous logo attachment metadata
      $slogo = AdiosGenerator_Utilities::get_attachment_by_post_name( "site-logo" );
      if($slogo) {
        delete_post_meta( $slogo->ID, 'adiosgenerator_disable_lazyload' );
        delete_post_meta( $slogo->ID, 'adiosgenerator_prioritize_background' );
      }

      WP_CLI::success( __( 'Logo has been set, attachment ID: ' . $logo, 'adiosgenerator' ) );
    } else {
      WP_CLI::error( __( 'Failed to set logo', 'adiosgenerator' ) );
    }
  }

  public function site_logo_2( $args, $assoc_args ) {
    $apidata = $this->appWpTokenGet( $assoc_args );
    if( !$apidata ) return;

    $retdata = $apidata->client;
    $divi = (array) json_decode( json_encode($apidata->divi), true );

    $logoparams = array(
      "text" => ucwords($retdata->site_name),
      "width" => 400,
      "height" => 70,
      "background" => implode(",", AdiosGenerator_Utilities::hexToRgba( $divi["accent_color"] )),
      "color" => implode(",", AdiosGenerator_Utilities::hexToRgba( "#FFFFFF" )),
      "fontSize" => 25,
      "font" => $retdata->header_font,
      "format" => ".png"
    );
    $logoparams = http_build_query( $logoparams );
    $mainLogoPlaceholder = ADIOSGENERATOR_LOGOMAKER_URL . "/logomaker/logo?{$logoparams}";
    $thelogo = $retdata->logo ? $retdata->logo : $mainLogoPlaceholder;
    $logo = AdiosGenerator_Utilities::upload_file_by_url(
      $thelogo,
      sanitize_title( $retdata->site_name . "-logo-2" ),
      sanitize_title( $retdata->site_name . "-logo-2" )
    );
    
    if( $logo ) {
      update_option( AdiosGenerator_Utilities::et_adiosgenerator_option("logo_2"), $logo );
      WP_CLI::success( __( 'Logo has been set, attachment ID: ' . $logo, 'adiosgenerator' ) );
    } else {
      WP_CLI::error( __( 'Failed to set logo 2', 'adiosgenerator' ) );
    }
  }


  public function favicon( $args, $assoc_args ) {
    $apidata = $this->appWpTokenGet( $assoc_args );
    if( !$apidata ) return;

    $retdata = $apidata->client;
    $divi = (array) json_decode( json_encode($apidata->divi), true );

    $placeholdFavicon = array(
      "text" => ucwords(substr( $retdata->site_name, 0, 1 )),
      "width" => 512,
      "height" => 512,
      "background" => implode(",", AdiosGenerator_Utilities::hexToRgba( $divi["accent_color"] )),
      "color" => implode(",", AdiosGenerator_Utilities::hexToRgba( "#FFFFFF" )),
      "fontSize" => 500,
      "font" => $retdata->header_font,
      "format" => ".png"
    );
    $placeholdFavicon = http_build_query( $placeholdFavicon );
    $placeholdFavicon = ADIOSGENERATOR_LOGOMAKER_URL . "/logomaker/favicon?{$placeholdFavicon}";
    $thefavicon = $retdata->favicon ? $retdata->favicon : $placeholdFavicon;
    $favicon = AdiosGenerator_Utilities::upload_file_by_url(
      $thefavicon,
      sanitize_title( $retdata->site_name . "-favicon" ),
      sanitize_title( $retdata->site_name . "-favicon" )
    );
    
    if( $favicon ) {
      update_option( 'site_icon', $favicon );
      WP_CLI::success( __( 'Logo has been set, attachment ID: ' . $favicon, 'adiosgenerator' ) );
    } else {
      WP_CLI::error( __( 'Failed to set favicon', 'adiosgenerator' ) );
    }
  }


  public function process_content( $args, $assoc_args ) {
    $apidata = $this->appWpTokenGet( $assoc_args );
    if( !$apidata ) return;

    $retdata = $apidata->client;
    $placeholder = $apidata->placeholder;
    $divi = (array) json_decode( json_encode($apidata->divi), true );

    $posts = get_posts(array(
      'posts_per_page' => -1,
      'post_type' => array(
        "page",
        "post",
        "project",
        "et_body_layout",
        "et_footer_layout",
        "et_header_layout",
        "et_template",
        "et_theme_builder"
      )
    ));

    $prevColors = get_option( AdiosGenerator_Utilities::et_adiosgenerator_option( "colors" ), array(
      'accent_color' => et_get_option( 'accent_color', $divi["accent_color"] ),
      'secondary_accent_color' => et_get_option( 'secondary_accent_color', $divi["secondary_accent_color"] )
    ));


    $logo = get_option( AdiosGenerator_Utilities::et_adiosgenerator_option( "logo" ) );
    $logo2 = get_option( AdiosGenerator_Utilities::et_adiosgenerator_option( "logo_2" ) );
    $smFields = (new ET_Builder_Module_Social_Media_Follow_Item)->get_fields();

    foreach( $posts as $pst ) {
      $content = $pst->post_content;

      // accents replace
      $content = preg_replace('/' . preg_quote($prevColors["accent_color"], '/') . '/i', $divi["accent_color"], $content);
      $content = preg_replace('/' . preg_quote($prevColors["secondary_accent_color"], '/') . '/i', $divi["secondary_accent_color"], $content);

      // logos replace
      $content = preg_replace('#https?://[^\s\'"]*/site-logo\.[a-zA-Z0-9]+#', wp_get_attachment_url( $logo ), $content);
      $content = preg_replace('#https?://[^\s\'"]*/site-logo-secondary\.[a-zA-Z0-9]+#', wp_get_attachment_url( $logo2 ), $content);

      // contact number replace
      $content = str_replace($placeholder->contact_number, $retdata->contact_number, $content);
      $content = str_replace(
        preg_replace('/\D+/', '', $placeholder->contact_number),
        preg_replace('/\D+/', '', $retdata->contact_number),
        $content
      );

      // email replace
      $content = str_replace($placeholder->email_address, $retdata->email_address, $content);

      // maps and address replace
      $content = str_replace( $placeholder->site_address, $retdata->site_address, $content);
      $content = (new AdiosGenerator_Process_Content)->replace_google_maps_iframe_address( $content, $retdata->site_address );
      $content = str_replace( 
        str_replace( " ", "+", $placeholder->site_address ), 
        str_replace( " ", "+", $retdata->site_address ),
        $content
      );

      // social media replace
      $content = preg_replace_callback(
      '/\[et_pb_social_media_follow([^\]]*)\](.*?)\[\/et_pb_social_media_follow\]/s',
        function ($matches) use($retdata, $smFields) {
            $attributes = $matches[1]; // Keeps the original attributes
            $socMediaContent = "";
            foreach( $retdata->social_media as $socmed ) {
              if( isset( $smFields['social_network']['value_overwrite'][$socmed->social] ) ) {
                $socMediaColor = $smFields['social_network']['value_overwrite'][$socmed->social];
                $socMediaContent .= "[et_pb_social_media_follow_network {$attributes} social_network=\"{$socmed->social}\" url=\"{$socmed->link}\" background_color=\"{$socMediaColor}\"]{$socmed->social}[/et_pb_social_media_follow_network]";
              }
            }
            return "[et_pb_social_media_follow{$attributes}]{$socMediaContent}[/et_pb_social_media_follow]";
        },
        $content
      );

      // insights replace
      $content = str_replace( $placeholder->about_content, $retdata->insights, $content );

      // socials replace
      $socialPages = array();
      foreach( $retdata->social_media as $socmed ) {
        $socialPages[$socmed->social] = $socmed->link;
        if( $socmed->social === "twitter" ) {
          $socialPages["x"] = $socmed->link;
        }
      }

      $social_platforms = implode('|', array_keys($socialPages));
      $social_pattern = '/https:\/\/[a-z0-9\-\.]*(' . $social_platforms . ')[a-z0-9\-\.]*\.com\/' . preg_quote( $placeholder->social_slug, '/' ) . '/i';
      
      $content = preg_replace_callback($social_pattern, function ($matches) use ($socialPages) {
        return $socialPages[$matches[1]] ?? $matches[0]; // Fallback if not found
      }, $content);

      // site name and slogan
      $content = str_replace( $placeholder->site_name, $retdata->site_name, $content );
      $content = str_replace( $placeholder->tagline, $retdata->slogan, $content );

      wp_update_post([
        'ID' => $pst->ID,
        'post_content' => $content
      ]);
      
    }
    
    $this->clear();
    WP_CLI::success( __( 'All contents pages, layouts and builder has been synced!', 'adiosgenerator' ) );
  }
}

WP_CLI::add_command(
  'adiosgenerator',
  'AdiosGenerator_WPCli'
);