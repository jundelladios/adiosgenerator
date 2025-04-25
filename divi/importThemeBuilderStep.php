<?php

if( !function_exists( 'adiosgenerator_theme_builder_api_import_theme_builder_step' ) ):

  function adiosgenerator_theme_builder_api_import_theme_builder_step( WP_REST_Request $request ) {
  
    $_POST = (array) json_decode($request->get_body(), true);
  
    $_         = et_();
    $id        = sanitize_text_field( $_->array_get( $_POST, 'id', '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is done in `et_builder_security_check`.
    $step      = (int) $_->array_get( $_POST, 'step', 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is done in `et_builder_security_check`.
    $chunk     = (int) $_->array_get( $_POST, 'chunk', 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is done in `et_builder_security_check`.
    $transient = 'et_theme_builder_import_' . get_current_user_id() . '_' . $id;
    $export    = get_transient( $transient );
  
    if ( false === $export ) {
      wp_send_json_error();
    }
  
    $layout_keys             = array( 'header', 'body', 'footer' );
    $portability             = et_core_portability_load( 'et_builder' );
    $steps                   = $export['steps'];
    $ready                   = empty( $steps );
    $layout_id_map           = $export['layout_id_map'];
    $presets                 = $export['presets'];
    $presets_rewrite_map     = $export['presets_rewrite_map'];
    $import_presets          = $export['import_presets'];
    $library_template_import = $export['library_template_import'];
    $file_name               = $export['file_name'];
    $cloud_item_editor       = $export['cloud_item_editor'];
    $temp_import             = $export['temp_import'];
    $duplicate_presets       = $export['duplicate_presets'];
    $preset_prefix           = $export['preset_prefix'];
    $templates               = array();
    $template_settings       = array();
    $chunks                  = 1;
    $preset_id               = 0;
  
    if ( ! $ready ) {
      $import_step                        = et_theme_builder_api_import_theme_builder_load_layout( $portability, $steps[ $step ]['id'], $steps[ $step ]['group'] );
      $import_step                        = array_merge( $import_step, array( 'presets' => $presets ) );
      $import_step                        = array_merge( $import_step, array( 'presets_rewrite_map' => $presets_rewrite_map ) );
      $import_step['import_presets']      = $import_presets;
      $import_step['is_update_preset_id'] = ! empty( $preset_prefix );
  
      if ( $temp_import ) {
        $import_step['data']['post_status'] = 'draft';
      }
  
      $result = $portability->import_theme_builder( $id, $import_step, count( $steps ), $step, $chunk );
  
      if ( false === $result ) {
        wp_send_json_error();
      }
  
      $ready  = $result['ready'];
      $chunks = $result['chunks'];
  
      foreach ( $result['layout_id_map'] as $old_id => $new_ids ) {
        $layout_id_map[ $old_id ] = array_merge(
          $_->array_get( $layout_id_map, $old_id, array() ),
          $new_ids
        );
      }
    }
  
    if ( $ready ) {
      if ( $import_presets && is_array( $presets ) && ! empty( $presets ) ) {
        if ( false === $duplicate_presets && ! $preset_prefix ) {
          $presets = $portability->prepare_to_import_non_duplicate_presets( $presets );
        }
  
        $override_defaults = ! empty( $preset_prefix );
  
        if ( ! $portability->import_global_presets( $presets, false, $override_defaults, $preset_prefix, true ) ) {
          $presets_error = apply_filters( 'et_core_portability_import_error_message', '' );
  
          if ( $presets_error ) {
            wp_send_json_error(
              array(
                'code'  => ET_Theme_Builder_Api_Errors::PORTABILITY_IMPORT_PRESETS_FAILURE,
                'error' => $presets_error,
              )
            );
          }
        }
      }
  
      $portability->delete_temp_files( $transient );
  
      $conditions     = array();
      $global_layouts = array();
  
      foreach ( $export['templates'] as $index => $template ) {
        $sanitized  = et_theme_builder_sanitize_template( $template );
        $is_default = $_->array_get( $sanitized, 'default', false );
  
        foreach ( $layout_keys as $key ) {
          $old_layout_id = (int) $_->array_get( $sanitized, array( 'layouts', $key, 'id' ), 0 );
          $layout_id     = et_()->array_get( $layout_id_map, array( $old_layout_id, 'template_' . $index ), '' );
          $layout_id     = ! empty( $layout_id ) ? $layout_id : 0;
  
          $_->array_set( $sanitized, array( 'layouts', $key, 'id' ), $layout_id );
  
          if ( $is_default ) {
            $global_layouts[ $key ]['id'] = $layout_id;
          }
        }
  
        $conditions = array_merge( $conditions, $sanitized['use_on'], $sanitized['exclude_from'] );
        $_->array_set( $sanitized, array( 'global_layouts' ), $global_layouts );
  
        $templates[] = $sanitized;
      }
  
      // Load all conditions from templates.
      $conditions        = array_unique( $conditions );
      $template_settings = array_replace(
        et_theme_builder_get_flat_template_settings_options(),
        et_theme_builder_load_template_setting_options( $conditions )
      );
      $valid_settings    = array_keys( $template_settings );
  
      // Strip all invalid conditions from templates.
      foreach ( $templates as $index => $template ) {
        $templates[ $index ]['use_on']       = array_values( array_intersect( $template['use_on'], $valid_settings ) );
        $templates[ $index ]['exclude_from'] = array_values( array_intersect( $template['exclude_from'], $valid_settings ) );
      }
  
      if ( $library_template_import ) {
        $is_multi_template = count( $templates ) > 1;
  
        if ( $is_multi_template || 'set' === $cloud_item_editor ) {
          $template_settings['set_name'] = $file_name;
  
          foreach ( $templates as $key => $template ) {
            foreach ( array( 'body', 'header', 'footer' ) as $layout_type ) {
              $layout_id = $_->array_get( $template, array( 'layouts', $layout_type, 'id' ) );
              if ( 'use_global' === $layout_id && isset( $global_layouts[ $layout_type ] ) ) {
                $global_layout_id = $_->array_get( $global_layouts, array( $layout_type, 'id' ) );
                $_->array_set( $templates, array( $key, 'layouts', $layout_type, 'id' ), $global_layout_id );
              }
            }
          }
  
          if ( $temp_import ) {
            $template_settings['post_status'] = 'draft';
          }
  
          $preset_id = et_theme_builder_save_preset_to_library( $templates, $template_settings );
        } else {
          $first_template = $templates[0];
          if ( 'template' === $cloud_item_editor ) {
            $template_settings['template_name'] = $first_template['title'];
          } else {
            $template_settings['template_name'] = $file_name;
          }
  
          if ( $temp_import ) {
            $first_template['status'] = 'draft';
          }
  
          $templates[0]['template_id'] = et_theme_builder_save_template_to_library( $first_template, $template_settings );
        }
      }
    } else {
      set_transient(
        $transient,
        array_merge(
          $export,
          array(
            'layout_id_map' => $layout_id_map,
          )
        ),
        60 * 60 * 24
      );
    }
  
    wp_send_json_success(
      array(
        'presetId'         => $preset_id,
        'chunks'           => $chunks,
        'templates'        => $templates,
        'templateSettings' => $template_settings,
      )
    );
  }
  
  endif;