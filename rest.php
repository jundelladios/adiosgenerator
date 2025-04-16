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
});