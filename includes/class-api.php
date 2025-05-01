<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

/**
 * 
 * WP Cli commands for adiosgenerator
 */
class AdiosGenerator_Api {

  private $endpoint;
  private $params = array();

  public function __construct( $endpoint, $params ) {
    $this->endpoint = $endpoint;
    $this->params = $params;
  }

  // possibly use for another endpoint
  public static function run( $endpoint, $params ) {
    $instance = new static( $endpoint, $params );
    return $instance->execute();
  }

  // call this method if using web generator api
  public static function generatorapi( $endpoint ) {
    return ADIOSGENERATOR_API_URL . $endpoint;
  }

  // executing post method only.
  private function execute() {
    $apiParams = array(
      'headers' => array(
        'Content-Type' => 'application/json',
        'Accept' => 'application/json'
      ),
      'body' => json_encode(array(
        "json" => $this->params
      ))
    );

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