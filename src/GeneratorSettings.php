<?php

namespace WebGenerator;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

use WebGenerator\GeneratorAdminActions;
use WebGenerator\GeneratorOptimization;
use Carbon_Fields\Container;
use Carbon_Fields\Field;

class GeneratorSettings {

  public function init() {
    add_action( 'carbon_fields_register_fields', array( $this, 'register_fields' ) );
    add_action( 'after_setup_theme', function() {
      \Carbon_Fields\Carbon_Fields::boot();
    });

    $this->init_post_meta_fields();

    // lead connector support
    add_action( 'admin_enqueue_scripts', array( $this, 'lead_connector_support' ) );
    add_action( 'admin_enqueue_scripts', array( $this, 'setting_assets' ) );
  }

  public function register_fields() {
    // Main container with tabs
    $googleFontAction = GeneratorAdminActions::generate_action_url( 'diva_google_fonts_optimize' );
      Container::make( 'theme_options', __( 'Diva Launch' ) )
      ->set_page_file( 'diva-launch-options' )
      ->set_icon( 'dashicons-admin-settings' );

      add_action('admin_menu', function() {
          remove_submenu_page('diva-launch-options', 'diva-launch-options');
      }, 999);

      add_action('admin_menu', function() {
        add_submenu_page(
          'diva-launch-options',
          'Tutorials',
          'Tutorials',
          'manage_options',
          '#diva-tutorials'
        );


        add_submenu_page(
          'diva-launch-options',
          'Support',
          'Support',
          'manage_options',
          '#diva-support'
        );

      }, 20);


      Container::make( 'theme_options', __( 'Optimization' ) )
      ->set_page_parent( 'diva-launch-options' )
      ->add_tab( __( 'Image Optimization' ), array(
          Field::make( 'textarea', 'diva_generator_force_eager_images', 'Force Eager Images' )
              ->set_help_text( 'Enter image filenames (one per line, use this for global elements only, ie: header) that should load eagerly. Separated by new line. ex: hero-image.jpg' ),
          Field::make( 'textarea', 'diva_generator_force_lazy_images', 'Force Lazy Images' )
              ->set_help_text( 'Enter image filenames (one per line, use this for global elements only, ie: footer) that should load lazily. Separated by new line. ex: below-the-fold.jpg' ),
      ))
      ->add_tab( __( 'JavaScript Optimization' ), array(
          Field::make( 'textarea', 'diva_generator_exclude_delay_scripts', 'Exclude Delay Javascripts' )
              ->set_help_text( 'Enter script keywords to exclude from delay (one per line). Separated by new line. ex: script.min.js' ),
      ))
      ->add_tab( __( 'CSS Optimization' ), array(
          Field::make( 'textarea', 'diva_generator_critical_css_lists', 'Critical CSS to be inlined [PATTERNED]' )
              ->set_help_text( 'Enter CSS patterns (one per line). Separated by new line. ex: style\.min\.css' )
      ))
      ->add_tab( __( 'Preloading' ), array(
          Field::make( 'textarea', 'diva_generator_preload_lists_removal', 'Remove mistakenly preloaded handle [PATTERNED]' )
            ->set_help_text( 'Enter CSS handles to remove from preload (one per line). Separated by new line. ex: preloaded\.min\.css' ),
    ));
  }




  /**
   * Register Carbon Fields meta fields for per-post settings.
   */
  public function register_post_meta_fields() {
    // Prevent duplicate registration of meta fields
    // Register per-post optimization meta fields for all public post types
    $post_types = get_post_types( array( 'public' => true ), 'names' );
    foreach ( $post_types as $post_type ) {
      Container::make( 'post_meta', __( 'Diva Launch Optimization' ) )
      ->where('post_type', $post_type)
      ->add_tab( __( 'Image Optimization' ), array(
            Field::make( 'textarea', 'diva_generator_force_eager_images', 'Force Eager Images' )
                ->set_help_text( 'Enter image filenames (one per line, use this for global elements only, ie: header) that should load eagerly. Separated by new line. ex: hero-image.jpg' ),
            Field::make( 'textarea', 'diva_generator_force_lazy_images', 'Force Lazy Images' )
                ->set_help_text( 'Enter image filenames (one per line, use this for global elements only, ie: footer) that should load lazily. Separated by new line. ex: below-the-fold.jpg' ),
        ))
        ->add_tab( __( 'JavaScript Optimization' ), array(
            Field::make( 'textarea', 'diva_generator_exclude_delay_scripts', 'Exclude Delay Javascripts' )
                ->set_help_text( 'Enter script keywords to exclude from delay (one per line). Separated by new line. ex: script.min.js' ),
        ))
        ->add_tab( __( 'CSS Optimization' ), array(
            Field::make( 'textarea', 'diva_generator_critical_css_lists', 'Critical CSS to be inlined [PATTERNED]' )
                ->set_help_text( 'Enter CSS patterns (one per line). Separated by new line. ex: style\.min\.css' ),
        ))
        ->add_tab( __( 'Preloading' ), array(
            Field::make( 'textarea', 'diva_generator_preload_lists_removal', 'Remove mistakenly preloaded handle [PATTERNED]' )
              ->set_help_text( 'Enter CSS handles to remove from preload (one per line). Separated by new line. ex: preloaded\.min\.css' ),
      ));
    }
  }

  /**
   * Initialize per-post meta fields registration.
   */
  public function init_post_meta_fields() {
    add_action( 'carbon_fields_register_fields', array( $this, 'register_post_meta_fields' ) );
  }


  public function lead_connector_support() {
    // 

    // Register a dummy handle
    wp_register_script('leadconnector-widget', '', [], null, true);

    // Enqueue it so inline code attaches
    wp_enqueue_script('leadconnector-widget');

    // Add the inline script
    wp_add_inline_script('leadconnector-widget', "
        const script = document.createElement('script');
        script.src = 'https://widgets.leadconnectorhq.com/loader.js';
        script.setAttribute('data-widget-id', '687fa90b97bc46aefd73216c');
        script.setAttribute('data-resources-url', 'https://widgets.leadconnectorhq.com/chat-widget/loader.js');
        document.body.appendChild(script);
    ");
  }


  public function setting_assets() {
    // admin assets
    wp_enqueue_style( 'adiosgenerator_admin', constant('ADIOSGENERATOR_PLUGIN_URI') . 'assets/admin.css' );
    wp_enqueue_script(
      'adiosgenerator_admin',
      constant('ADIOSGENERATOR_PLUGIN_URI') . 'assets/admin.js',
      [],
      null,
      true
    );

    wp_localize_script( 'adiosgenerator_admin', 'adiosgenerator_admin', [
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce'    => wp_create_nonce('adiosgenerator_admin'),
      'path' => $_SERVER['REQUEST_URI'],
      'support_link' => constant('ADIOSGENERATOR_API_URL') . "/support",
      'tutorial' => constant( 'ADIOSGENERATOR_TUTORIALS_URL' ),
    ]);
  }
}