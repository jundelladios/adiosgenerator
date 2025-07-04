<?php

namespace WebGenerator;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

/**
 * 
 * Class to handle contents replacements upon sync
 */
class GeneratorGravityForms {

  public function init() {
    add_filter( 'gform_submit_button', array( $this, "divi_css_class_button" ), 10, 2 );
    add_action( 'get_footer', array( $this, "divi_gravity_form_theme" ) );
    add_filter( 'gform_init_scripts_footer', '__return_true' );
  }
  
  public function divi_css_class_button( $button, $form ) {
    $fragment = \WP_HTML_Processor::create_fragment( $button );
    $fragment->next_token();
    $fragment->add_class( 'et_pb_button et_pb_gform_button' );
    return $fragment->get_updated_html();
  }

  public function divi_gravity_form_theme() {
    wp_enqueue_style( 'gform-divi-adiosgenerator', ADIOSGENERATOR_PLUGIN_URI . 'assets/gravityforms.css' );
  }
}