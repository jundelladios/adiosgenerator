<?php

namespace WebGenerator;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

/**
 * 
 * Class to handle contents replacements upon sync
 */
class GeneratorProcessContent {

  public function replace_google_maps_iframe_address($content, $address) {
    return preg_replace_callback(
      '#(<iframe[^>]+src="[^"]*google\.com/maps/embed[^"]*?\?[^"]*?)q=([^"&]*)#i',
      fn($m) => $m[1] . 'q=' . urlencode($address),
      $content
    );
  }

  public function get_social_items() {
    if( class_exists( 'ET_Builder_Module_Social_Media_Follow_Item' )) {
      return wp_send_json_success((new \ET_Builder_Module_Social_Media_Follow_Item)->get_fields());
    }
    return wp_send_json_success([]);
  }

}
