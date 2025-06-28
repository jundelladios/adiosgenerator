<?php

namespace WebGenerator;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

/**
 * 
 * WP Cli commands for adiosgenerator
 */
class GeneratorAPI {

  private $endpoint;
  private $params = array();
  private $token = null;

  public function __construct( $endpoint, $params, $token ) {
    $this->endpoint = $endpoint;
    $this->params = $params;
    $this->token = $token;
  }

  // possibly use for another endpoint
  public static function run( $endpoint, $params, $token=null ) {
    $instance = new static( $endpoint, $params, $token );
    return $instance->execute();
  }

  // call this method if using web generator api
  public static function generatorapi( $endpoint ) {
    return constant("ADIOSGENERATOR_API_URL") . $endpoint;
  }

  private function getToken() {
    if( !defined('DIVA_LAUNCH_APIKEY') ) {
      return null;
    }
    $apiKey = constant("DIVA_LAUNCH_APIKEY");
    if( !$apiKey ) return null;

    $apiParams = array(
      'headers' => array(
        'Content-Type' => 'application/json',
        'Accept' => 'application/json'
      ),
      'body' => json_encode(array(
        "json" => array(
          "token" => constant("DIVA_LAUNCH_APIKEY")
        )
      ))
    );

    $request = wp_remote_post( self::generatorapi( "/api/trpc/appTokens.oauth" ), $apiParams);
    if( is_wp_error( $request )) {
      return false;
    }

    $body = wp_remote_retrieve_body( $request );
    $json = json_decode( $body );
    if( !$json ) { return false; }
    if( isset( $json->error ) ) { return false; }

    $resp = self::getResponse( $json );
    return $resp;
  }

  // executing post method only.
  private function execute() {
    if( !$this->token ) {
      $this->token = $this->getToken();
    }

    $apiParams = array(
      'headers' => array(
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
      ),
      'body' => json_encode(array(
        "json" => $this->params
      ))
    );

    $apiParams['headers']['Authorization'] = "Bearer {$this->token}";

    $request = wp_remote_post( $this->endpoint, $apiParams);
    if( is_wp_error( $request )) {
      return false;
    }

    $body = wp_remote_retrieve_body( $request );
    $json = json_decode( $body );
    if( !$json ) { return false; }
    if( isset( $json->error ) ) { return false; }

    return $json;
  }

  public static function getResponse( $data ) {
    if(!isset($data->result->data->json)) { return null; }
    try {
      return $data->result->data->json;
    } catch(\Exception $e) {
      return null;
    }
  }
}