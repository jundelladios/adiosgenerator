<?php

namespace WebGenerator;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

use WebGenerator\GeneratorAPI;

class GeneratorSSO {
  
  private $token;
  private $redirect;
  private $post;

  /**
   * SSO constructor
   *
   * @param string $token
   * @param string $redirect
   * @param int $post
   */
  public function __construct( $token, $redirect, $post ) {
    $this->token = $token;
    $this->redirect = $redirect;
    $this->post = $post;
  }

  /**
   * Check if token exists
   *
   * @return void
   */
  public function check() {
    if( !empty( $this->token ) ) { return true; }
    return false;
  }

  /**
   * Initialize sso after plugin loaded
   *
   * @param string $token
   * @param string $redirect
   * @param int $post
   * @return void
   */
  public static function init( $token, $redirect, $post ) {
    $instance = new static( $token, $redirect, $post );
    if($instance->check()) {
      $instance->run();
    }
  }

  /**
   * Run sso
   *
   * @return void
   */
  public function run() {
    try {
      $data = $this->getData();
      $this->handleSession( $data );
      $this->redirect( $data );
    } catch(\Exception $e) {
      /**
       * ensure to redirect to get out with the token.
       * force redirect even unauthorized
       */
      $this->redirect( $data );
    }
  }

  /**
   * Handles session
   *
   * @param [type] $data
   * @return void
   */
  private function handleSession( $data ) {
    if( !$data ) { return false; }

    /**
     * starts to handle session
     */
    $tokendetails = GeneratorAPI::getResponse( $data );
    $user = isset($tokendetails->user) ? $tokendetails->user : "";
    $password = isset($tokendetails->password) ? $tokendetails->password : "";
    $email = isset($tokendetails->email) ? $tokendetails->email : "";
    
    if(empty( $user ) || empty( $password )) { return false; }

    /**
     * authenticate wp credential from token
     */
    $auth = wp_authenticate( $user, $password );
    if( is_wp_error( $auth )) {
      return false;
    }

    $user = get_user_by( "email", $email ? $email : $user );
    $this->loginUser( $user );
  }

  /**
   * Execute auth user
   *
   * @param \WP_user $user
   * @return void
   */
  private function loginUser(\WP_user $user) {
    /**
     * Set WP User session
     */
    wp_set_current_user($user->ID, $user->user_login);

    wp_set_auth_cookie($user->ID);

    /**
     * Fires the plugin action for successfully logged in
     */
    do_action('adiosgenerator/login', $user->user_login, $user);

    /**
     * Fires wp login
     */
    do_action('wp_login', $user->user_login, $user);
  }

  /**
   * handles redirect for sso
   *
   * @param mixed $data
   * @return void
   */
  private function redirect( $data ) {
    if( $data && !empty( $this->post ) && get_permalink( $this->post ) ) {
      wp_redirect( get_permalink( $this->post ) . "?et_fb=1&PageSpeed=off" );
      die();
    }
    if( $data && !empty( $this->redirect )) {
      wp_redirect( home_url( $this->redirect ) );
      die();
    }
    wp_redirect( admin_url() );
    die();
  }

  /**
   * Get the application data from wp generator portal
   *
   * @return void
   */
  private function getData() {
    $data = GeneratorAPI::run(
      GeneratorAPI::generatorapi( "/api/trpc/appTokens.wpvalidate" ),
      array(
        "token" => $this->token
      )
    );
    return $data;
  }
}