<?php

namespace WebGenerator;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

class GeneratorProcessContent {

  /**
   * Replace google maps iframe address
   *
   * @param [type] $content
   * @param [type] $address
   * @return void
   */
  public function replace_google_maps_iframe_address($content, $address) {
    return preg_replace_callback(
      '#(<iframe[^>]+src="[^"]*google\.com/maps/embed[^"]*?\?[^"]*?)q=([^"&]*)#i',
      fn($m) => $m[1] . 'q=' . urlencode($address),
      $content
    );
  }
}
