<?php

namespace WebGenerator;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

use WebGenerator\GeneratorAPI;
use WebGenerator\GeneratorCache;
use WebGenerator\GeneratorAdminActions;

class GeneratorAdminBar {

  /**
   * Initialize custom cache settings
   *
   * @return void
   */
  public function init() {
    add_action( 'admin_bar_menu', array( $this, "admin_bar_menu" ), 1000 );
    add_action('admin_init', array( $this, "admin_bar_exec" ) );
    add_action( 'admin_notices', array( $this, "admin_bar_messages" ));
  }

  /**
   * Display clear cache in admin bar
   *
   * @param mixed $admin_bar
   * @return void
   */
  public function admin_bar_menu( $admin_bar  ) {
    if ( ! current_user_can( 'manage_options' ) ) { return; } // Security check
    
    $admin_bar->add_node( array(
      'id'     => 'diva_generator_menu',
      'title' => '<span class="ab-icon dashicons dashicons-admin-plugins"></span><span class="ab-label">Diva Launch</span>',
      'href'  => constant('ADIOSGENERATOR_API_URL'),
      'meta'   => [
        'title'  => 'Diva Launch',
        'target' => '_blank'
      ],
    ));

    // clear cache
    $admin_bar->add_node([
        'id'     => 'diva_generator_menu_clear_cache',
        'parent' => 'diva_generator_menu',
        'title'  => 'Clear Cache',
        'href'   => GeneratorAdminActions::generate_action_url( 'diva_clear_cache' )
    ]);
    
    // force application update
    $admin_bar->add_node([
        'id'     => 'diva_generator_menu_app_force_update',
        'parent' => 'diva_generator_menu',
        'title'  => 'Application Force Update',
        'href'   => GeneratorAdminActions::generate_action_url( 'diva_force_update' )
    ]);

    // php recommendations apply
    $admin_bar->add_node([
        'id'     => 'diva_generator_menu_php_recommendations',
        'parent' => 'diva_generator_menu',
        'title'  => 'PHP Recommendations',
        'href'   =>  GeneratorAdminActions::generate_action_url( 'diva_php_recommendations' )
    ]);

    // security fixes fresh wp installs
    $admin_bar->add_node([
        'id'     => 'diva_generator_menu_security_fixes',
        'parent' => 'diva_generator_menu',
        'title'  => 'Security Fixes',
        'href'   =>  GeneratorAdminActions::generate_action_url( 'diva_security_fixes' )
    ]);

    // flush permalinks
    $admin_bar->add_node([
        'id'     => 'diva_generator_menu_flush_permalinks',
        'parent' => 'diva_generator_menu',
        'title'  => 'Flush Permalinks',
        'href'   =>  GeneratorAdminActions::generate_action_url( 'diva_flush_permalinks' )
    ]);
  }


  public function admin_bar_exec() {
    if( GeneratorAdminActions::validate_action( 'diva_clear_cache' ) ) {
      GeneratorCache::clear_cache();
      GeneratorAdminActions::redirect_action( 'diva_clear_cache' );
      exit;
    }

    if( GeneratorAdminActions::validate_action( 'diva_force_update' ) ) {
      GeneratorAPI::run(
        GeneratorAPI::generatorapi( "/api/trpc/appTokens.updateApp" ),
        array()
      );
      GeneratorAdminActions::redirect_action( 'diva_force_update' );
      exit;
    }

    if( GeneratorAdminActions::validate_action( 'diva_php_recommendations' ) ) {
      GeneratorAPI::run(
        GeneratorAPI::generatorapi( "/api/trpc/appTokens.phpRecommendation" ),
        array()
      );
      GeneratorAdminActions::redirect_action( 'diva_php_recommendations' );
      exit;
    }

    if( GeneratorAdminActions::validate_action( 'diva_security_fixes' ) ) {
      GeneratorAPI::run(
        GeneratorAPI::generatorapi( "/api/trpc/appTokens.securityFixes" ),
        array()
      );
      GeneratorAdminActions::redirect_action( 'diva_security_fixes' );
      exit;
    }

    if( GeneratorAdminActions::validate_action( 'diva_flush_permalinks' ) ) {
      flush_rewrite_rules();
      GeneratorAdminActions::redirect_action( 'diva_flush_permalinks' );
      exit;
    }
  }

  public function admin_bar_messages() {
    // clear cache message
    GeneratorAdminActions::display_action_message( "diva_clear_cache", "Diva Launch cache has been cleared!" );
    GeneratorAdminActions::display_action_message( "diva_force_update", "Application has been updated!" );
    GeneratorAdminActions::display_action_message( "diva_php_recommendations", "PHP recommendations has been applied!" );
    GeneratorAdminActions::display_action_message( "diva_security_fixes", "Security Fixes has been applied! Please refresh this app." );
    GeneratorAdminActions::display_action_message( "diva_flush_permalinks", "Permalinks has been flushed!" );
  }
}