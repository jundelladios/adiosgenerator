<?php

namespace WebGenerator;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

use WebGenerator\GeneratorAPI;

class GeneratorCache {

  /**
   * Initialize custom cache settings
   *
   * @return void
   */
  public function init() {
    add_action( 'admin_bar_menu', array( $this, "admin_cache_clear" ), 1000 );
    add_action( 'adiosgenerator_clear_cache', array( $this, 'clear_cache_call' ) );
    add_action ('admin_init', array( $this, "execute_cache_clear" ) );
    add_action( 'admin_notices', array( $this, "cache_clear_message" ));
  }

  /**
   * Handles clear all cache via web generator
   *
   * @return void
   */
  public function clear_cache_call() {
    self::clear_cache();
  }

  /**
   * Handles cloudflare cache clear
   *
   * @return void
   */
  public static function cloudflare_clear() {
    $parsed_url = parse_url(home_url());
    $domain = $parsed_url['host'];
    GeneratorAPI::run(
      GeneratorAPI::generatorapi( "/api/trpc/cw.cloudflareClear" ),
      array(
        "hostname" => $domain
      )
    );
  }

  /**
   * Clear all cache
   *
   * @return void
   */
  public static function clear_cache() {
    if(class_exists("ET_Core_PageResource")) {
      \ET_Core_PageResource::remove_static_resources( "all", "all" );
      et_core_clear_transients();
      et_core_clear_wp_cache();
    }
    do_action( 'breeze_clear_all_cache' );
    wp_cache_flush();
    self::cloudflare_clear();
  }

  /**
   * Display clear cache in admin bar
   *
   * @param mixed $admin_bar
   * @return void
   */
  public function admin_cache_clear( $admin_bar  ) {
    if ( ! current_user_can( 'manage_options' ) ) { return; } // Security check
    $cache_clear_url = admin_url( '?action=adiosgenerator_purge&_wpnonce=' . wp_create_nonce( 'adios_generator' ) . '&redirect=' . urlencode( home_url( add_query_arg( null, null ) ) ) );
    
    $admin_bar->add_node( array(
      'id'     => 'generator-purge-all',
      'title' => 'Generator Clear Cache',
      'href'  => $cache_clear_url, // Replace with your desired URL
    ));
  }

  /**
   * Execute clear cache via url with nonce
   *
   * @return void
   */
  public function execute_cache_clear() {
    if (isset($_GET['action']) && $_GET['action'] === 'adiosgenerator_purge') {
      if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'adios_generator')) {
        wp_die('Security check failed');
      }
      $this->clear_cache_call();
      set_transient('adiosgenerator_cleaned' . get_current_user_id(), true, 30);
      // possibly redirect to previous url
      wp_redirect( isset( $_GET['redirect'] ) ? $_GET['redirect'] : admin_url() );
      exit;
    }
  }

  /**
   * Display message after cache successfully cleared
   *
   * @return void
   */
  public function cache_clear_message() {
    if (get_transient('adiosgenerator_cleaned' . get_current_user_id())) {
      ?>
      <div class="notice notice-success is-dismissible">
        <p>Web Generator cache has been cleared!</p>
      </div>
      <?php
       delete_transient('adiosgenerator_cleaned' . get_current_user_id());
    }
  }
}