<?php

namespace WebGenerator;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

use WebGenerator\GeneratorLogging;

class GeneratorAPI {

  private $endpoint;
  private $params = array();
  private $token = null;

  /**
   * Web generator api constructor
   *
   * @param string $endpoint
   * @param mixed $params
   * @param string $token optional
   */
  public function __construct( $endpoint, $params, $token ) {
    $this->endpoint = $endpoint;
    $this->params = $params;
    $this->token = $token;
  }

  /**
   * Run api from web generator
   *
   * @param [type] $endpoint
   * @param [type] $params
   * @param [type] $token
   * @return void
   */
  public static function run( $endpoint, $params, $token=null ) {
    $instance = new static( $endpoint, $params, $token );
    return $instance->execute();
  }

  /**
   * Web generator api endpoint
   *
   * @param string $endpoint
   * @return string
   */
  public static function generatorapi( $endpoint ) {
    return constant("ADIOSGENERATOR_API_URL") . $endpoint;
  }

  /**
   * Get and request authorization token from web generator
   *
   * @return string
   */
  private function getToken() {
    if( !defined('DIVA_LAUNCH_APIKEY') ) {
      return null;
    }
    $apiKey = constant("DIVA_LAUNCH_APIKEY");
    if( !$apiKey ) return null;

    $apiParams = array(
      'timeout' => 86400,
      'headers' => array(
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Pragma'        => 'no-cache',
        'Expires'       => '0',
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

  /**
   * Execute api
   *
   * @return void
   */
  private function execute() {
    if( !$this->token ) {
      $this->token = $this->getToken();
    }

    $apiParams = array(
      'timeout' => 86400,
      'headers' => array(
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Pragma'        => 'no-cache',
        'Expires'       => '0',
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

  /**
   * Get api response in trpc format
   *
   * @param [type] $data
   * @return void
   */
  public static function getResponse( $data ) {
    if(!isset($data->result->data->json)) { return null; }
    try {
      return $data->result->data->json;
    } catch(\Exception $e) {
      return null;
    }
  }
}