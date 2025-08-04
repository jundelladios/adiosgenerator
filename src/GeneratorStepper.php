<?php

namespace WebGenerator;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

use WebGenerator\GeneratorUtilities;

class GeneratorStepper {

  /**
   * Initialize media feature
   *
   * @return void
   */
  public function init() {
    
    if( get_option( GeneratorUtilities::et_adiosgenerator_option( "client_application" ), 0 ) ) {
      add_action( 'wp_enqueue_scripts', array( $this, 'stepper_scripts' ) );
      add_action( 'admin_enqueue_scripts', array( $this, 'stepper_scripts' ) );
      add_action( 'wp_ajax_wp_adiosgenerator_stepper_ajax', array( $this, 'wp_adiosgenerator_stepper_ajax' ));
    }
  }


  public function wp_adiosgenerator_stepper_ajax() {
    check_ajax_referer('adiosgenerator_stepper', 'security');

    $stepvalue = isset( $_POST['step'] ) ? $_POST['step'] : null;
    $step = update_option( GeneratorUtilities::et_adiosgenerator_option( "step" ), $stepvalue );

    return $stepvalue;
  }

  public function stepper_scripts() {

    if ( !is_user_logged_in() ) {
      return;
    }

    wp_enqueue_style( 'adiosgenerator_stepper', constant('ADIOSGENERATOR_API_URL') . '/jslibs/instruction-stepper/style.css' );
    wp_enqueue_script(
      'adiosgenerator_stepper',
      constant('ADIOSGENERATOR_API_URL') . '/jslibs/instruction-stepper/scripts.js',
      [],
      null,
      true
    );



    wp_enqueue_script(
      'adiosgenerator_stepper-assists',
      constant('ADIOSGENERATOR_PLUGIN_URI') . 'assets/stepper-assists.js',
      ['adiosgenerator_stepper'],
      null,
      true
    );

    wp_localize_script( 'adiosgenerator_stepper', 'adiosgenerator_stepper', [
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce'    => wp_create_nonce('adiosgenerator_stepper'),
      'step' => get_option( GeneratorUtilities::et_adiosgenerator_option( "step" ) ),
      'path' => $_SERVER['REQUEST_URI'],
      'tutorial' => admin_url( 'admin.php?page=diva-tutorials' ),
      'is_adminbar' => is_admin_bar_showing()
    ]);
  }
}