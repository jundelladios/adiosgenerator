<?php

namespace WebGenerator;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

class GeneratorMedia {

  /**
   * Initialize media feature
   *
   * @return void
   */
  public function init() {
    add_action( 'wp_enqueue_scripts', array( $this, 'media_opt_label' ) );
    add_action( 'admin_enqueue_scripts', array( $this, 'media_opt_label' ) );

    // attachment optimization settings
    add_filter( 'attachment_fields_to_edit',  array( $this, "media_settings" ), 10, 2 );
    add_filter( "attachment_fields_to_save", array( $this, "media_settings_save" ), null, 2); 

    // ajax media statuses
    add_action('wp_ajax_wp_adiosgenerator_media_statuses', array( $this, 'wp_adiosgenerator_media_statuses' ));
  }

  /**
   * Additional Settings in Media to handle LCP
   *
   * @return void
   */
  private function attachment_fields() {
    return array(
      array(
        "label" => "LCP: Disable Lazyload Media",
        "name" => "adiosgenerator_disable_lazyload",
        "options" => array(
          "No" => "0",
          "Yes" => "1"
        ),
        "helps" => __( 'If set, Make sure this attachment is in the ABOVE THE FOLD content.', 'adiosgenerator' )
      ),
      array(
        "label" => "LCP: Preload Image",
        "name" => "adiosgenerator_prioritize_background",
        "options" => array(
          "No" => "0",
          "Desktop - High Priority" => "1",
          "Desktop - Low Priority" => "2",
          "Mobile - High Priority" => "3",
          "Mobile - Low Priority" => "4",
          "All Media - High Priority" => "5",
          "All Media - Low Priority" => "6",
          "Neutral" => "7",
          "Desktop - Neutral" => "8",
          "Mobile - Neutral" => "9"
        ),
        "helps" => __( "If set, Make sure this attachment is in the ABOVE THE FOLD content. (High for backgrounds, Low for sliders, Neutral undecided as long this image has been prioritized)", 'adiosgenerator' )
      ),
      array(
        "label" => "LCP: Preload with SRCSETs",
        "name" => "adiosgenerator_preload_srcset",
        "options" => array(
          "No" => "0",
          "Yes" => "1"
        ),
        "helps" => __( 'If preload is using img tag and sercset, enable this.', 'adiosgenerator' )
      ),
    );
  }
  
  /**
   * Display form fields from additional settings
   *
   * @param mixed $form_fields
   * @param mixed $post
   * @return void
   */
  public function media_settings(  $form_fields, $post ) {
    $fields = $this->attachment_fields();
    foreach( $fields as $field ) {
      $value = get_post_meta($post->ID, $field['name'], true);
      
      $attach = "attachments[{$post->ID}][{$field['name']}]";
      $select = "<select name=\"{$attach}\" value=\"{$value}\">";
      foreach( $field['options'] as $key => $opt ) {
        $selected = $value == $opt ? "selected" : "";
        $select .= "<option value=\"{$opt}\" {$selected}>{$key}</option>";
      }
      $select .= "<select>";

      $form_fields[$field['name']] = array(
        'label' => __( $field['label'], 'adiosgenerator' ),
        'input' => 'html',
        'html' => $select,
        'value' => $value,
        'helps' => $field['helps']
      );
    }

    return $form_fields;
  }

  /**
   * Saving additional settings feature in media
   *
   * @param mixed $post
   * @param mixed $attachment
   * @return void
   */
  public function media_settings_save( $post, $attachment ) {
    $fields = $this->attachment_fields();
    foreach( $fields as $field ) {
      $value = isset( $attachment[$field['name']] ) ? $attachment[$field['name']] : "0";
      update_post_meta($post['ID'], $field['name'], $value);
    }
    return $post;
  }


  public function wp_adiosgenerator_media_statuses() {
    check_ajax_referer('adiosgenerator_media', 'security');

    $dislazy = get_posts( [
      'post_type'  => 'attachment',
      'meta_key'   => 'adiosgenerator_disable_lazyload',
      'meta_value' => '1',
      'fields'     => 'ids',
      'numberposts' => -1,
    ]);

    $preloaded = get_posts( [
      'post_type'  => 'attachment',
      'fields'     => 'ids',
      'numberposts' => -1,
      'meta_query' => array(
        array(
          'key'     => 'adiosgenerator_prioritize_background',
          'value'   => '0',
          'compare' => '!='
        )
      )
    ]);
    

    $data = [
      'status' => 'ok',
      'message' => array(
        'dislazy' => $dislazy,
        'preloaded' => $preloaded
      ),
      'timestamp' => time()
    ];

    wp_send_json( $data );
  }

  /**
   * Handles global variable for script and media label js to display if media was preloaded or lazyloaded
   *
   * @param string $hook
   * @return void
   */
  public function media_opt_label( $hook ) {
    // allow if logged in only
    if( !is_user_logged_in() ) {
      return;
    }

    wp_enqueue_style( 'adiosgenerator-media-label', constant('ADIOSGENERATOR_PLUGIN_URI') . 'assets/media-label.css' );

    wp_enqueue_script(
      'adiosgenerator-media-label',
      constant('ADIOSGENERATOR_PLUGIN_URI') . 'assets/media-label.js',
      [ 'jquery' ],
      null,
      true
    );

    wp_localize_script( 'adiosgenerator-media-label', 'adiosgenerator_media', [
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce'    => wp_create_nonce('adiosgenerator_media')
    ]);
  }
}