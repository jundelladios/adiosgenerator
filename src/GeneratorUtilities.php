<?php

namespace WebGenerator;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

class GeneratorUtilities {

  /**
   * Get attachment by post name
   * @param string $post_name name of post to be filtered
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

  /**
   * Reusable code for upload url file to wp media
   * @param string $file url of the file
   * @param string $alt alt of the file and it is optional
   * @return int $attachment_id attachment id
   */
  public static function uploadFile( $file, $alt ) {
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

    // image metadata
    if( str_contains( $sideload[ 'type' ], "image" )) {
      // update medatata, regenerate image sizes
      require_once( ABSPATH . 'wp-admin/includes/image.php' );

      wp_update_attachment_metadata(
        $attachment_id,
        wp_generate_attachment_metadata( $attachment_id, $sideload[ 'file' ] )
      );

      if($alt) {
        update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
      }
    }

    return $attachment_id;
  }
  
  /**
   * Actual method for upload file by url
   * @param string $image_url image or file url
   * @param string $alt optional alt of the image
   * @param string $fname modify filename of the file upon upload
   * @return int attachment id
   */
  public static function upload_file_by_url( $image_url, $alt = null, $fname=null ) {
    // it allows us to use download_url() and wp_handle_sideload() functions
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    require_once( ABSPATH . 'wp-admin/includes/media.php' );

    $extension = pathinfo(strtok($image_url, '?'), PATHINFO_EXTENSION);
    $fname = $fname ? sanitize_title( $fname ) . ".{$extension}" : null;

    // prevent redownload if filename already exists or uploaded.
    $filename = basename(parse_url($image_url, PHP_URL_PATH));
    $thefileName = sanitize_file_name( $fname ? $fname : $filename );

    $attachment = self::get_attachment_by_post_name( $thefileName );
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
      'name'     => $thefileName,
      'type'     => mime_content_type( $temp_file ),
      'tmp_name' => $temp_file,
      'size'     => filesize( $temp_file ),
    );

    return self::uploadFile( $file, $alt);
  }

  /**
   * Method to temporarily disable wp post revisions
   */
  public static function disable_post_revision() {
    // removing auto post revision
    add_filter( 'wp_revisions_to_keep', '__return_zero' );
  }

  /**
   * Getters for option_name prefix
   *
   * @param [type] $option
   * @return void
   */
  public static function et_adiosgenerator_option( $option ) {
    return "et_adiosgenerator_option_{$option}";
  }


  /**
   * Hex to rgb conversion
   *
   * @param string $hex
   * @param integer $alpha
   * @return array
   */
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


  /**
   * Duplicate wp post including taxonomies and meta fields
   *
   * @param string $id
   * @param string $title
   * @param string $status
   * @return void
   */
  public static function duplicate_post( $post_id, $title="", $status="draft" ) {
    $post = get_post($post_id);
    if (!$post) return false;

    $admin_user = get_users([
      'role'    => 'administrator',
      'orderby' => 'ID',
      'order'   => 'ASC',
      'number'  => 1,
    ]);

    $admin_id = wp_list_pluck(
      get_users([
        'role'    => 'administrator',
        'orderby' => 'ID',
        'order'   => 'ASC',
        'number'  => 1,
      ]),
      'ID'
    )[0];

    // Create new post object
    $new_post = array(
        'post_title'     => $title ? $title : $post->post_title . ' (Copy)',
        'post_content'   => $post->post_content,
        'post_status'    => $status ? $status : 'draft',
        'post_type'      => $post->post_type,
        'post_author'    => function_exists( 'get_current_user_id' ) ? get_current_user_id() : $admin_id,
        'post_excerpt'   => $post->post_excerpt,
        'post_parent'    => $post->post_parent,
        'menu_order'     => $post->menu_order,
        'post_password'  => $post->post_password,
        'comment_status' => $post->comment_status,
        'ping_status'    => $post->ping_status,
    );

    // Insert new post
    $new_post_id = wp_insert_post($new_post);

    if (is_wp_error($new_post_id)) return false;

    // Copy taxonomies
    $taxonomies = get_object_taxonomies($post->post_type);
    foreach ($taxonomies as $taxonomy) {
        $terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
        wp_set_object_terms($new_post_id, $terms, $taxonomy);
    }

    // Copy meta fields
    $meta = get_post_meta($post_id);
    foreach ($meta as $key => $values) {
      foreach ($values as $value) {
        add_post_meta($new_post_id, $key, maybe_unserialize($value));
      }
    }

    return $new_post_id;
  }
}