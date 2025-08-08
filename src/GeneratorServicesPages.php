<?php

namespace WebGenerator;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

use WebGenerator\GeneratorAPI;
use ET_Core_PageResource;

class GeneratorServicesPages {

  private $post_name = "diva_services";

  /**
   * Services Pages Registeration
   *
   * @return void
   */
  public function init() {
    add_action( 'init', array( $this, "register_services_pages" ));
    add_filter( 'et_builder_post_types', array( $this, 'enable_divi_builder_on_services_pages' ) );
    add_filter( 'template_include', array( $this, 'force_blank_template_for_services_pages' ));
  }

  public function force_blank_template_for_services_pages( $template ) {
    if (is_singular($this->post_name)) {
        $blank_template = get_template_directory() . '/page-template-blank.php';
        if (file_exists($blank_template)) {
            return $blank_template;
        }
    }
    return $template;
  }

  // ensure use divi theme for incoming posts
  public function enable_divi_builder_on_services_pages( $post_types  ) {
    $custom_post_types = get_post_types( array( 'public' => true ), 'names' );
    return array_unique( array_merge( $post_types, $custom_post_types ) );
  }

  public function register_services_pages() {
    $labels = array(
        'name'               => 'Services',
        'singular_name'      => 'Service',
        'menu_name'          => 'Services',
        'name_admin_bar'     => 'Service',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Service',
        'new_item'           => 'New Service',
        'edit_item'          => 'Edit Service',
        'view_item'          => 'View Service',
        'all_items'          => 'All Services',
        'search_items'       => 'Search Services',
        'not_found'          => 'No Services found',
        'not_found_in_trash' => 'No Services found in Trash',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => false,
        'rewrite'            => array('slug' => 'services'),
        'show_in_rest'       => true,
        'hierarchical'       => true,
        'supports'           => array('title', 'editor', 'thumbnail', 'page-attributes'),
        'menu_icon'          => 'dashicons-hammer',
    );

    register_post_type( $this->post_name, $args );
  }
}