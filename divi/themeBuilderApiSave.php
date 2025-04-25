<?php

if(!function_exists('adiosgenerator_theme_builder_api_save')):

  function adiosgenerator_theme_builder_api_save( WP_REST_Request $request ) {

    $_POST = (array) json_decode($request->get_body(), true);
    
    // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce is done in `et_builder_security_check`.
    $_                   = et_();
    $live                = '1' === $_->array_get( $_POST, 'live', '0' );
    $first_request       = '1' === $_->array_get( $_POST, 'first_request', '1' );
    $last_request        = '1' === $_->array_get( $_POST, 'last_request', '1' );
    $templates           = wp_unslash( $_->array_get( $_POST, 'templates', array() ) );
    $processed_templates = wp_unslash( $_->array_get( $_POST, 'processed_templates', array() ) );
    $library_tb_id       = (int) $_->array_get( $_POST, 'library_theme_builder_id', 0 );
    $library_item_id     = (int) $_->array_get( $_POST, 'library_item_id', 0 );
    $theme_builder_id    = $library_tb_id ? $library_tb_id : et_theme_builder_get_theme_builder_post_id( $live, true );
    $has_default         = '1' === $_->array_get( $_POST, 'hasDefault', '0' );
    $updated_ids         = array();
    // phpcs:enable

    // Remove this action as it not necessary when we're saving entire TB.
    // save_post_cb is a heavy operation and significanlty slows down the saving of TB.
    // We remove static page resources after TB save below in this function.
    remove_action( 'save_post', array( 'ET_Core_PageResource', 'save_post_cb' ), 10, 3 );

    $templates_to_process = array();

    // Populate the templates.
    foreach ( $templates as $index => $template ) {
      $templates_to_process[ $_->array_get( $template, 'id', 'unsaved_' . $index ) ] = $template;
    }

    $affected_templates = array();
    error_Log( "TEMPLATES PROCESS " . json_encode($templates));

    // Update or insert templates.
    foreach ( $templates_to_process as $template ) {
      $raw_post_id = $_->array_get( $template, 'id', 0 );
      $post_id     = is_numeric( $raw_post_id ) ? (int) $raw_post_id : 0;
      $new_post_id = et_theme_builder_store_template( $theme_builder_id, $template, ! $has_default );

      if ( ! $new_post_id ) {
        continue;
      }

      $is_default = get_post_meta( $new_post_id, '_et_default', true ) === '1';

      if ( $is_default ) {
        $has_default = true;
      }

      // Add template ID into $affected_templates for later use
      // to Add mapping template ID to theme builder ID
      // and delete existing template mapping.
      $affected_templates[] = array(
        'raw'         => $raw_post_id,
        'normalized'  => $post_id,
        'new_post_id' => $new_post_id,
      );
    }

    error_log( "AFFECTED TEMPLATES " . json_encode( $affected_templates ));

    foreach ( $affected_templates as $template_pair ) {
      if ( $template_pair['normalized'] !== $template_pair['new_post_id'] ) {
        $updated_ids[ $template_pair['raw'] ] = $template_pair['new_post_id'];
      }
    }

    if ( $last_request ) {
      $existing_templates = get_post_meta( $theme_builder_id, '_et_template', false );

      if ( $existing_templates ) {
        // Store existing template mapping as backup to avoid data lost
        // when user interrupting the saving process before completed.
        update_option( 'et_tb_templates_backup_' . $theme_builder_id, $existing_templates );
      }

      // Delete existing template mapping.
      delete_post_meta( $theme_builder_id, '_et_template' );

      $processed_templates = array_merge( $processed_templates, $affected_templates );

      // Insert new template mapping.
      foreach ( $processed_templates as $template_pair ) {
        add_post_meta( $theme_builder_id, '_et_template', $template_pair['new_post_id'] );
      }

      // Delete existing template mapping backup.
      delete_option( 'et_tb_templates_backup_' . $theme_builder_id );

      if ( $live ) {
        et_theme_builder_trash_draft_and_unused_posts();
      }

      et_theme_builder_clear_wp_cache( 'all' );

      // Remove static resources on save. It's necessary because how we are generating the dynamic assets for the TB.
      ET_Core_PageResource::remove_static_resources( 'all', 'all', false, 'dynamic' );
    }

    // Edit Template and Edit Preset: Save the templates into local library.
    if ( $library_tb_id && $library_item_id ) {
      et_theme_builder_update_library_item( $library_item_id, $templates );
    }

    error_log( "FIRE ACTION SAVE_POST" );

    // Add this action back.
    add_action( 'save_post', array( 'ET_Core_PageResource', 'save_post_cb' ), 10, 3 );

    wp_send_json_success(
      array(
        'updatedTemplateIds'     => (object) $updated_ids,
        'processedTemplatesData' => (object) $affected_templates,
        'hasDefault'             => $has_default ? '1' : '0',
      )
    );
  }

endif;