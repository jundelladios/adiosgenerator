<?php

/**
 * Plugin Name: AD-IOS Web Generator
 * Plugin URI: https://github.com/jundelladios?tab=repositories
 * Description: Web Generator from AD-IOS Team
 * Version: 1.0.0
 * Author: Jundell
 * Author URI: mailto:jundell@ad-ios.com
 * License: GPL-2.0+
 * Text Domain: adiosgenerator
 * Domain Path: /languages
 */

 add_action( 'rest_api_init', function () {
  register_rest_route( 'adiosgenerator', 'adiosgenerator_et_theme_builder_api_reset', array(
    'methods' => 'POST',
    'callback' => 'adiosgenerator_et_theme_builder_api_reset',
    'permission_callback' => function() {
      return current_user_can( 'edit_others_posts' );
    }
  ));
});

function adiosgenerator_et_theme_builder_api_reset() {
  if(function_exists( 'et_theme_builder_api_reset' )) {
    $live_id = et_theme_builder_get_theme_builder_post_id( true, false );
    if ( $live_id > 0 && current_user_can( 'delete_others_posts' ) ) {
      wp_trash_post( $live_id );
      // Reset cache when theme builder is reset.
      ET_Core_PageResource::remove_static_resources( 'all', 'all', true );
    }
    et_theme_builder_trash_draft_and_unused_posts();
    wp_send_json_success();
  }
  wp_send_json_error();
}

add_action( 'admin_enqueue_scripts', 'adiosgenerator_admin_enqueue_scripts' );
function adiosgenerator_admin_enqueue_scripts() {
  $isWPGenerator = isset($_GET['wpgenerator']) ? true : false;
  if($isWPGenerator) {
    wp_enqueue_style(
      'adios-generator-wpadmin',
      plugins_url( 'style.css', __FILE__ )
    );
  }
}