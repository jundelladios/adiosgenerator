<?php

namespace WebGenerator;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

use WebGenerator\GeneratorAPI;
use WP_CLI_Command;
use WP_CLI;

# traits
use WebGenerator\WpCliTraits\ClearCache;
use WebGenerator\WpCliTraits\SyncApplication;
use WebGenerator\WpCliTraits\SyncTemplate;
use WebGenerator\WpCliTraits\SiteLogo;
use WebGenerator\WpCliTraits\SiteLogoSecondary;
use WebGenerator\WpCliTraits\SiteFavicon;
use WebGenerator\WpCliTraits\StockPhotos;
use WebGenerator\WpCliTraits\ProcessContent;
use WebGenerator\WpCliTraits\AIGenerateContent;
use WebGenerator\WpCliTraits\ServicesPages;
use WebGenerator\WpCliTraits\SEOPages;

// WP Cli commands for adiosgenerator
class GeneratorCLI extends WP_CLI_Command {

  // cli's
  use ClearCache;
  use SyncApplication;
  use SyncTemplate;
  use SiteLogo;
  use SiteLogoSecondary;
  use SiteFavicon;
  use StockPhotos;
  use ProcessContent;
  use AIGenerateContent;
  use ServicesPages;
  use SEOPages;

  /**
   * Handles required parameter from cli's
   *
   * @param [type] $assoc_args
   * @param array $params
   * @return void
   */
  private function requiredParams( $assoc_args, $params = array()) {
    foreach( $params as $param ) {
      if( !isset( $assoc_args[$param] ) ) {
        WP_CLI::error( sprintf( __( 'You need to specify the --%s=<value> parameter', 'adiosgenerator' ), $param ) );
        return false;
      }
    }
    return true;
  }

  /**
   * Validate application tokens either app or template
   *
   * @param mixed $assoc_args
   * @param string $endpoint
   * @return void
   */
  private function appWpTokenGet( $assoc_args, $endpoint="appWpSync" ) {
    if( !$this->requiredParams( $assoc_args, array('token') ) ) {
      return false;
    }

    $token = $assoc_args['token'];
    $data = GeneratorAPI::run(
      GeneratorAPI::generatorapi( "/api/trpc/appTokens.{$endpoint}" ),
      array(),
      $token
    );
    $apidata = GeneratorAPI::getResponse( $data );
    if(!$apidata) {
      WP_CLI::error( __( 'Failed to load your data. App token is invalid!', 'adiosgenerator' ) );
      return false;
    }
    GeneratorUtilities::disable_post_revision();
    return $apidata;
  }

  /**
   * Posts to be handled upon replacements
   *
   * @return void
   */
  private function get_posts_content_generate() {
    $posts = get_posts(array(
      'posts_per_page' => -1,
      'post_type' => array(
        "page",
        "post",
        "project",
        "diva_services",
        "et_body_layout",
        "et_footer_layout",
        "et_header_layout",
        "et_template",
        "et_theme_builder"
      )
    ));

    return $posts;
  }
  
}