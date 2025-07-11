<?php

namespace WebGenerator;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

class GeneratorMime {

  /**
   * Initialize allowed important mime types support
   *
   * @return void
   */
  public function init() {
    add_filter('upload_mimes', array( $this, "upload_mimes" ), 999);
    add_filter('wp_check_filetype_and_ext', array( $this, "force_allow_upload" ), 999, 4);
  }
  
  /**
   * Mime types support
   *
   * @param mixed $mimes
   * @return void
   */
  public function upload_mimes( $mimes ) {
    $mimes['ico'] = 'image/x-icon';
    return $mimes;
  }

  /**
   * Forcing allowed mime types in wp
   *
   * @param mixed $data
   * @param mixed $file
   * @param mixed $filename
   * @param mixed $mimes
   * @return void
   */
  public function force_allow_upload( $data, $file, $filename, $mimes ) {
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    if ($ext === 'ico') {
      return [
        'ext'  => 'ico',
        'type' => 'image/x-icon',
        'proper_filename' => $filename,
      ];
    }
    return $data;
  }
}