<?php

namespace WebGenerator;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

class GeneratorUtilities {

  /**
   * Get attachment by post name
   */
  public static function get_attachment_by_post_name( $post_name ) {
    $args = array(
      'posts_per_page' => 1,
      'post_type'      => 'attachment',
      'name'           => trim( $post_name ),
    );

    $get_attachment = new \WP_Query( $args );
    if ( ! $get_attachment || ! isset( $get_attachment->posts, $get_attachment->posts[0] ) ) {
      return false;
    }

    return $get_attachment->posts[0];
  }



  public static function upload_file_by_url( $image_url, $alt = null, $fname=null ) {
    // it allows us to use download_url() and wp_handle_sideload() functions
    require_once( ABSPATH . 'wp-admin/includes/file.php' );

    $extension = pathinfo($image_url, PATHINFO_EXTENSION);
    $fname = $fname ? sanitize_title( $fname ) . ".{$extension}" : null;

    // prevent redownload if filename already exists or uploaded.
    $filename = basename( $image_url );
    $attachment = self::get_attachment_by_post_name( $fname ? $fname : $filename );
    if($attachment) {
      return $attachment->ID;
    }

    // download to temp dir
    $temp_file = download_url( $image_url );

    if( is_wp_error( $temp_file ) ) {
      return false;
    }

    // move the temp file into the uploads directory
    $file = array(
      'name'     => $fname ? $fname : basename( $image_url ),
      'type'     => mime_content_type( $temp_file ),
      'tmp_name' => $temp_file,
      'size'     => filesize( $temp_file ),
    );

    $sideload = wp_handle_sideload(
      $file,
      array(
        'test_form'   => false // no needs to check 'action' parameter
      )
    );

    if( ! empty( $sideload[ 'error' ] ) ) {
      // you may return error message if you want
      return false;
    }

    // it is time to add our uploaded image into WordPress media library
    $attachment_id = wp_insert_attachment(
      array(
        'guid'           => $sideload[ 'url' ],
        'post_mime_type' => $sideload[ 'type' ],
        'post_title'     => basename( $sideload[ 'file' ] ),
        'post_content'   => '',
        'post_status'    => 'inherit',
      ),
      $sideload[ 'file' ]
    );

    if( is_wp_error( $attachment_id ) || ! $attachment_id ) {
      return false;
    }

    // update medatata, regenerate image sizes
    require_once( ABSPATH . 'wp-admin/includes/image.php' );

    wp_update_attachment_metadata(
      $attachment_id,
      wp_generate_attachment_metadata( $attachment_id, $sideload[ 'file' ] )
    );

    if($alt) {
      update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
    }

    return $attachment_id;
  }


  public static function disable_post_revision() {
    // removing auto post revision
    add_filter( 'wp_revisions_to_keep', '__return_zero' );
  }

  public static function et_adiosgenerator_option( $option ) {
    return "et_adiosgenerator_option_{$option}";
  }

  public static function hexToRgba($hex, $alpha=1) {
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
        $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
        $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
    } else if (strlen($hex) == 6) {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    } else {
        // Invalid hex color
        return false;
    }
    return [$r, $g, $b, $alpha];
  }
}