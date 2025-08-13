<?php

namespace WebGenerator;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

use WebGenerator\GeneratorUtilities;
use WebGenerator\GeneratorAPI;
use WebGenerator\GeneratorProcessContent;

class GeneratorREST {

  /**
   * Authorize API if key is valid in web portal
   *
   * @param \WP_REST_Request $request
   * @return void
   */
  public function authorize( \WP_REST_Request $request ) {
    $headers = $request->get_headers();
    $auth = $headers['authorization'][0] ?? '';
    preg_match('/Bearer\s+(.*)$/i', $auth, $matches);
    if(!isset( $matches[1])) {
      return null;
    }

    $token = trim($matches[1]);

    if( $token != constant( 'DIVA_LAUNCH_APIKEY' ) ) {
      return false;
    }

    return true;
  }

  /**
   * Retrieves the template data stored in the WordPress options table.
   *
   * This method fetches the option value associated with the template data,
   * decodes the JSON string into a PHP object, and returns it. The option
   * key is generated using the static method et_adiosgenerator_option from
   * the GeneratorUtilities class, with 'template_data' as the argument.
   *
   * @return mixed|null Returns the decoded template data object, or null if not found.
   */
  public function get_template() {
    $template = json_decode( get_option( GeneratorUtilities::et_adiosgenerator_option( 'template_data' ) ) );
    if( !isset( $template->template ) ) { 
      return wp_send_json_error( array(
        'message' => "Template data is missing"
      ));
    }
    return $template;
  }


  /**
   * Retrieves the client data stored in the WordPress options table.
   *
   * This method fetches the option value associated with the client data,
   * decodes the JSON string into a PHP object, and returns it. The option
   * key is generated using the static method et_adiosgenerator_option from
   * the GeneratorUtilities class, with 'client_data' as the argument.
   *
   * @return mixed|null Returns the decoded client data object, or null if not found.
   */
  public function get_client() {
    $client = json_decode( get_option( GeneratorUtilities::et_adiosgenerator_option( 'client_data' ) ) ); 
    if( !$client->client ) { 
      return wp_send_json_error( array(
        'message' => "Client data is missing"
      ));
    }
    return $client;
  }

  /**
   * Calls all methods in this class that start with 'init'
   *
   * This can be used to automatically initialize all features
   * whose methods are named with the 'init' prefix.
   *
   * @return void
   */
  public function routes() {
    GeneratorREST::autoload_routes(array(
      \WebGenerator\RestAPIs\SocialMediaItems::class,
      \WebGenerator\RestAPIs\InitTemplate::class,
      \WebGenerator\RestAPIs\SyncTemplate::class,
      \WebGenerator\RestAPIs\InitClient::class,
      \WebGenerator\RestAPIs\SyncClient::class,
      \WebGenerator\RestAPIs\Generate::class,
    ));
  }

  /**
   * Loads all classes that extend GeneratorREST and have a 'route' method.
   *
   * @param array $classList Array of fully qualified class names to check and load.
   * @return void
   */
  public static function autoload_routes(array $classList) {
    foreach ($classList as $class) {
      if (class_exists($class) && is_subclass_of($class, 'WebGenerator\GeneratorREST')) {
        if (method_exists($class, 'route')) {
          (new $class)->route();
        }
      }
    }
  }
}
