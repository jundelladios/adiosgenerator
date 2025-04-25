<?php

if ( ! function_exists( 'adiosgenerator_theme_builder_api_import_theme_builder' ) ):

  function adiosgenerator_theme_builder_api_import_theme_builder( WP_REST_Request $request ) {

    if ( ! current_user_can( 'edit_others_posts' ) ) {
      wp_send_json_error();
    }

    $_POST = (array) json_decode($request->get_body(), true);

    if(!defined( 'ET_BUILDER_DIR' )) {
      wp_send_json_error();
    }

    require_once( ABSPATH . 'wp-admin/includes/file.php' );

    $temp_file = download_url( $_POST['file'] );
    if( is_wp_error( $temp_file ) ) {
      wp_send_json_error();
    }

    $uploadedfile = array(
      'name'     => basename( $_POST['file'] ),
      'type'     => mime_content_type( $temp_file ),
      'tmp_name' => $temp_file,
      'size'     => filesize( $temp_file ),
    );

    $_      = et_();
    $upload = wp_handle_sideload(
      $uploadedfile,
      array(
        'test_size' => false,
        'test_type' => false,
        'test_form' => false,
      )
    );

    if ( ! $_->array_get( $upload, 'file', null ) ) {
      wp_send_json_error(
        array(
          'code'  => ET_Theme_Builder_Api_Errors::UNKNOWN,
          'error' => __( 'An unknown error has occurred. Please try again later.', 'adiosgenerator' ),
        )
      );
    }
  
    $export = json_decode( et_()->WPFS()->get_contents( $upload['file'] ), true );
  
    if ( null === $export ) {
      wp_send_json_error(
        array(
          'code'  => ET_Theme_Builder_Api_Errors::UNKNOWN,
          'error' => __( 'An unknown error has occurred. Please try again later.', 'adiosgenerator' ),
        )
      );
    }

    $portability = et_core_portability_load( 'et_theme_builder' );

    // if ( ! $portability->is_valid_theme_builder_export( $export ) ) {
    //   wp_send_json_error(
    //     array(
    //       'code'  => ET_Theme_Builder_Api_Errors::PORTABILITY_INCORRECT_CONTEXT,
    //       'error' => __( 'This file should not be imported in this context.', 'adiosgenerator' ),
    //     )
    //   );
    // }

    // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verfied in `et_builder_security_check`.
    $override_default_website_template = '1' === $_->array_get( $_POST, 'override_default_website_template', '0' );
    $import_presets                    = '1' === $_->array_get( $_POST, 'import_presets', '0' );
    $library_template_import           = '1' === $_->array_get( $_POST, 'library_template_import', '0' );
    $has_default_template              = $_->array_get( $export, 'has_default_template', false );
    $has_global_layouts                = $_->array_get( $export, 'has_global_layouts', false );
    $presets                           = $_->array_get( $export, 'presets', array() );
    $presets_rewrite_map               = array();
    $incoming_layout_duplicate         = false;
    $uploaded_file_name                = substr( sanitize_file_name( basename( $upload['file'] ) ), 0, -5 );
    $cloud_item_editor                 = $_->array_get( $_POST, 'cloud_item_editor', '' );
    $temp_import                       = '1' === $_->array_get( $_POST, 'temp_import', '0' );
    $preset_prefix                     = $_->array_get( $_POST, 'preset_prefix', '' );
    $duplicate_presets                 = filter_var( $_->array_get( $_POST, 'duplicate_presets', true ), FILTER_VALIDATE_BOOLEAN );

    // Maybe ask the user to make a decision on how to deal with global layouts.
    if ( ( ! $override_default_website_template || ! $has_default_template ) && $has_global_layouts ) {
      $incoming_layout_duplicate_decision = $_->array_get( $_POST, 'incoming_layout_duplicate_decision', '' );

      if ( 'duplicate' === $incoming_layout_duplicate_decision || $library_template_import ) {
        $incoming_layout_duplicate = true;
      } elseif ( 'relink' === $incoming_layout_duplicate_decision ) {
        $incoming_layout_duplicate = false;
      } else {
        wp_send_json_error(
          array(
            'code'  => ET_Theme_Builder_Api_Errors::PORTABILITY_REQUIRE_INCOMING_LAYOUT_DUPLICATE_DECISION,
            'error' => __( 'This import contains references to global layouts.', 'adiosgenerator' ),
          )
        );
      }
    }
    // phpcs:enable

    // Make imported preset overrides to avoid collisions with local presets.
    if ( $import_presets && is_array( $presets ) && ! empty( $presets ) && ! $preset_prefix ) {
      $presets_rewrite_map = $portability->prepare_to_import_layout_presets( $presets );
    }

    // Prepare import steps.
    $layout_id_map = array();
    $layout_keys   = array( 'header', 'body', 'footer' );
    $id            = md5( get_current_user_id() . '_' . uniqid( 'et_theme_builder_import_', true ) );
    $transient     = 'et_theme_builder_import_' . get_current_user_id() . '_' . $id;
    $steps_files   = array();

    foreach ( $export['templates'] as $index => $template ) {
      foreach ( $layout_keys as $key ) {
        $layout_id = (int) $_->array_get( $template, array( 'layouts', $key, 'id' ), 0 );

        if ( 0 === $layout_id ) {
          continue;
        }

        $layout = $_->array_get( $export, array( 'layouts', $layout_id ), null );

        if ( empty( $layout ) ) {
          continue;
        }

        // Use a temporary string id to avoid numerical keys being reset by various array functions.
        $template_id = 'template_' . $index;
        $is_global   = (bool) $_->array_get( $layout, 'theme_builder.is_global', false );
        $create_new  = ( $template['default'] && $override_default_website_template ) || ! $is_global || $incoming_layout_duplicate;

        if ( $create_new ) {
          $temp_id = 'tbi-step-' . count( $steps_files );

          et_theme_builder_api_import_theme_builder_save_layout( $portability, $template_id, $layout_id, $layout, $temp_id, $transient );

          $steps_files[] = array(
            'id'    => $temp_id,
            'group' => $transient,
          );
        } else {
          if ( ! isset( $layout_id_map[ $layout_id ] ) ) {
            $layout_id_map[ $layout_id ] = array();
          }

          $layout_id_map[ $layout_id ][ $template_id ] = 'use_global';
        }
      }
    }

    set_transient(
      $transient,
      array(
        'file_name'                         => $uploaded_file_name,
        'ready'                             => false,
        'steps'                             => $steps_files,
        'templates'                         => $export['templates'],
        'override_default_website_template' => $override_default_website_template,
        'incoming_layout_duplicate'         => $incoming_layout_duplicate,
        'layout_id_map'                     => $layout_id_map,
        'presets'                           => $presets,
        'import_presets'                    => $import_presets,
        'library_template_import'           => $library_template_import,
        'presets_rewrite_map'               => $presets_rewrite_map,
        'cloud_item_editor'                 => $cloud_item_editor,
        'temp_import'                       => $temp_import,
        'duplicate_presets'                 => $duplicate_presets,
        'preset_prefix'                     => $preset_prefix,
      ),
      60 * 60 * 24
    );

    wp_send_json_success(
      array(
        'id'    => $id,
        'steps' => count( $steps_files ),
      )
    );
  }

endif;