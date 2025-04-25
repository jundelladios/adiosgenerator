<?php

if( !function_exists( 'adiosgenerator_import_page_template' ) ) {

  function adiosgenerator_import_page_template( WP_REST_Request $request ) {

    if ( ! isset( $_POST['context'] ) ) {
      wp_send_json_error();
    }
  
    $context = sanitize_text_field( $_POST['context'] );
    $post_id = isset( $_POST['post'] ) ? (int) $_POST['post'] : 0;
    
    $portability = new ET_Core_Portability( $context );
    $portability->instance = (object) array(
      "context" => $context,
      "type" => "post",
      'target' => "et_pb_builder",
    );

    $json = (array) json_decode( file_get_contents( $_POST['file'] ), true );
    if(!isset( $json['data'] )) {
      wp_send_json_error();
    }
    
    $postContent = null;
    foreach( $json['data'] as $jsoncontent ) {
      $postContent = $jsoncontent;
    }

    if(!$postContent) {
      wp_send_json_error();
    }

    $beforeAfterImages = array();
    foreach( $json["images"] as $keyimg => $imgs ) {
      $imgAlt = preg_replace("/\.[^.]+$/", "", basename( $keyimg ));
      $imgAttach = adiosgenerator_upload_file_by_url( $keyimg, sanitize_title( $imgAlt ) );
      if($imgAttach) {
        $imgNewURL = wp_get_attachment_url( $imgAttach );
        $beforeAfterImages[ $keyimg ] = $imgNewURL;
      }
    }

    foreach( $beforeAfterImages as $key => $img ) {
      $postContent = str_replace( $key, $img, $postContent );
    }

    $shortcode_object = et_fb_process_shortcode( $postContent );
    $portability->import_global_presets( 
      $json['presets'],
      true,
      true,
      "WP Generator"
    );

    // set primary and secondary colors.
    $gcolors = et_builder_get_all_global_colors();
    $gcolors['gcid-primary-color'] = array(
      "color" => "#7e3bd0",
      "active" => "yes"
    );
    $gcolors['gcid-secondary-color'] = array(
      "color" => "#2b87da",
      "active" => "yes"
    );
    et_update_option( 'et_global_colors', $gcolors );

    wp_update_post(array(
      "ID" => $post_id,
      "post_content" => $postContent,
      "post_status" => "publish"
    ));

    ET_Core_PageResource::remove_static_resources( 'all', 'all', true );

    do_shortcode( $postContent );

    wp_send_json_success(array(
      "content" => $postContent
    ));
    
  }
}