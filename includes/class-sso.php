<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

class AdiosGenerator_SingleSignOn {
  
  private $token;
  private $redirect;

  public function __construct( $token, $redirect ) {
    $this->token = $token;
    $this->redirect = $redirect;
  }

  public function check() {
    if( !empty( $this->token ) ) { return true; }
    return false;
  }

  public static function init( $token, $redirect ) {
    $instance = new static( $token, $redirect );
    if($instance->check()) {
      $instance->run();
    }
  }

  public function run() {
    try {
      $data = $this->getData();
      $this->handleSession( $data );
    } catch(\Exception $e) {
      // nothing to do
    }
    
    /**
     * ensure to redirect to get out with the token.
     * force redirect
     */
    $this->redirect( $data );
  }

  private function handleSession( $data ) {
    if( !$data ) { return false; }

    /**
     * starts to handle session
     */
    $tokendetails = AdiosGenerator_Api::getResponse( $data );
    $user = isset($tokendetails->user) ? $tokendetails->user : "";
    $password = isset($tokendetails->password) ? $tokendetails->password : "";
    
    if(empty( $user ) || empty( $password )) { return false; }

    /**
     * authenticate wp credential from token
     */
    $auth = wp_authenticate( $user, $password );
    if( is_wp_error( $auth )) {
      return false;
    }

    $user = get_user_by( "email", $user );
    $this->loginUser( $user );
  }

  private function loginUser(WP_user $user) {
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

  private function redirect( $data ) {
    if( $data && !empty( $this->redirect )) {
      wp_redirect( $this->redirect );
    }
    wp_redirect( admin_url() );
  }

  /**
   * Fetch validate token from api
   */
  private function getData() {
    $data = AdiosGenerator_Api::run(
      AdiosGenerator_Api::generatorapi( "/api/trpc/appTokens.wpvalidate" ),
      array(
        "token" => $this->token
      )
    );
    return $data;
  }
}


function adiosgenerator_initialize_sso() {
  $token = isset( $_GET["wpgentoken"] ) ? $_GET["wpgentoken"] : "";
  $redirect = isset( $_GET["wpgenredirect"] ) ? $_GET["wpgenredirect"] : "";
  AdiosGenerator_SingleSignOn::init( $token, $redirect );
}
add_action('plugins_loaded', "adiosgenerator_initialize_sso");