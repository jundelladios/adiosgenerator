<?php

namespace WebGenerator;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

class GeneratorLogging {

  /**
   * Error logging for this plugin
   *
   * @param string $message
   * @return void
   */
  public static function message( $message ) {
    if (defined('ADIOSGENERATOR_DEBUG_LOG') && ADIOSGENERATOR_DEBUG_LOG) {
      error_log( "\n\n" . $message . "\n\n" );
    }
  }
}