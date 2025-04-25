<?php

add_action( 'rest_api_init', function () {

  register_rest_route( 'adiosgenerator', 'adiosgenerator_et_theme_builder_api_reset', array(
    'methods' => 'POST',
    'callback' => 'adiosgenerator_et_theme_builder_api_reset',
    'permission_callback' => function() {
      return current_user_can( 'edit_others_posts' );
    }
  ));

  register_rest_route( 'adiosgenerator', 'authorize', array(
    'methods' => 'GET',
    'callback' => 'adiosgenerator_signin',
    'permission_callback' => function() {
      return '__false';
    }
  ));

  register_rest_route( 'adiosgenerator', 'adiosgenerator_url_to_attachment', array(
    'methods' => 'POST',
    'callback' => 'adiosgenerator_url_to_attachment',
    'permission_callback' => function() {
      return current_user_can( 'edit_others_posts' );
    }
  ));

  register_rest_route( 'adiosgenerator', 'adiosgenerator_epanel_save', array(
    'methods' => 'POST',
    'callback' => 'adiosgenerator_epanel_save',
    'permission_callback' => function() {
      return current_user_can( 'edit_others_posts' );
    }
  ));

  register_rest_route( 'adiosgenerator', 'adiosgenerator_import_page_template', array(
    'methods' => 'POST',
    'callback' => 'adiosgenerator_import_page_template',
    'permission_callback' => function() {
      return current_user_can( 'edit_others_posts' );
    }
  ));


  register_rest_route( 'adiosgenerator', 'adiosgenerator_pages_menu', array(
    'methods' => 'POST',
    'callback' => 'adiosgenerator_pages_menu',
    'permission_callback' => function() {
      return current_user_can( 'edit_others_posts' );
    }
  ));

  // register_rest_route( 'adiosgenerator', 'adiosgenerator_theme_builder_api_import_theme_builder', array(
  //   'methods' => 'POST',
  //   'callback' => 'adiosgenerator_theme_builder_api_import_theme_builder',
  //   'permission_callback' => function() {
  //     return current_user_can( 'edit_others_posts' );
  //   }
  // ));


  // register_rest_route( 'adiosgenerator', 'adiosgenerator_theme_builder_api_import_theme_builder_step', array(
  //   'methods' => 'POST',
  //   'callback' => 'adiosgenerator_theme_builder_api_import_theme_builder_step',
  //   'permission_callback' => function() {
  //     return current_user_can( 'edit_others_posts' );
  //   }
  // ));


  // register_rest_route( 'adiosgenerator', 'adiosgenerator_theme_builder_api_save', array(
  //   'methods' => 'POST',
  //   'callback' => 'adiosgenerator_theme_builder_api_save',
  //   'permission_callback' => function() {
  //     return current_user_can( 'edit_others_posts' );
  //   }
  // ));
  
});