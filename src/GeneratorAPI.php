<?php

namespace WebGenerator;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

use WebGenerator\GeneratorLogging;

class GeneratorAPI {

  private $endpoint;
  private $params = array();

  /**
   * Web generator api constructor
   *
   * @param string $endpoint
   * @param mixed $params
   */
  public function __construct( $endpoint, $params ) {
    $this->endpoint = $endpoint;
    $this->params = $params;
  }

  /**
   * Run api from web generator
   *
   * @param [type] $endpoint
   * @param [type] $params
   * @return void
   */
  public static function run( $endpoint, $params ) {
    $instance = new static( $endpoint, $params );
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
   * Execute api
   *
   * @return void
   */
  private function execute() {
    $apiParams = array(
      'timeout' => 86400,
      'headers' => array(
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Pragma'        => 'no-cache',
        'Expires'       => '0',
        'Authorization' => constant('DIVA_LAUNCH_APIKEY')
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