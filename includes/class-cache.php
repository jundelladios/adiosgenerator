<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

/**
 * 
 * Class to handle contents replacements upon sync
 */
class AdiosGenerator_Cache {

  public function init() {
    add_action( 'admin_bar_menu', array( $this, "admin_cache_clear" ), 1000 );
    add_action( 'adiosgenerator_clear_cache', array( $this, 'clear_cache_call' ) );
    add_action ('admin_init', array( $this, "execute_cache_clear" ) );
    add_action( 'admin_notices', array( $this, "cache_clear_message" ));
  }

  public function clear_cache_call() {
    self::clear_cache();
  }

  public static function clear_cache() {
    $parsed_url = parse_url(home_url());
    $domain = $parsed_url['host'];
    AdiosGenerator_Api::run(
      AdiosGenerator_Api::generatorapi( "/api/trpc/cw.cloudflareClear" ),
      array(
        "hostname" => $domain
      )
    );
    
    if(class_exists("ET_Core_PageResource")) {
      ET_Core_PageResource::remove_static_resources( 'all', 'all' );
      ET_Core_PageResource::remove_static_resources( 'all', 'all', false, 'dynamic' );
      ET_Core_PageResource::remove_static_resources( 'all', 'all', true );
    }
    do_action( 'breeze_clear_all_cache' );
    wp_cache_flush();
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
      wp_redirect(admin_url('index.php?adiosgenerator_cleaned=1'));
      exit;
    }
  }

  public function cache_clear_message() {
    if (isset($_GET['adiosgenerator_cleaned'])) {
      ?>
      <div class="notice notice-success is-dismissible">
        <p>Web Generator cache has been cleared!</p>
        <a href="<?php echo admin_url(); ?>" class="notice-dismiss" style="text-decoration: none;">
          <span class="screen-reader-text">Dismiss this notice.</span>
        </a>
      </div>
      <?php
    }
  }
}

(new AdiosGenerator_Cache)->init();