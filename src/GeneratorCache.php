<?php

namespace WebGenerator;

use WebGenerator\GeneratorAPI;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

/**
 * 
 * Class to handle contents replacements upon sync
 */
class GeneratorCache {

  public function init() {
    add_action( 'admin_bar_menu', array( $this, "admin_cache_clear" ), 1000 );
    add_action( 'adiosgenerator_clear_cache', array( $this, 'clear_cache_call' ) );
    add_action ('admin_init', array( $this, "execute_cache_clear" ) );
    add_action( 'admin_notices', array( $this, "cache_clear_message" ));
  }

  public function clear_cache_call() {
    self::clear_cache();
  }

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

  public function admin_cache_clear( $admin_bar  ) {
    if ( ! current_user_can( 'manage_options' ) ) { return; } // Security check
    $cache_clear_url = admin_url( '?action=adiosgenerator_purge&_wpnonce=' . wp_create_nonce( 'adios_generator' ) );
    
    $admin_bar->add_node( array(
      'id'     => 'generator-purge-all',
      'title' => 'Generator Clear Cache',
      'href'  => $cache_clear_url, // Replace with your desired URL
    ));
  }

  public function execute_cache_clear() {
    if (isset($_GET['action']) && $_GET['action'] === 'adiosgenerator_purge') {
      if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'adios_generator')) {
        wp_die('Security check failed');
      }
      $this->clear_cache_call();
      set_transient('adiosgenerator_cleaned' . get_current_user_id(), true, 30);
      wp_redirect( admin_url() );
      exit;
    }
  }

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