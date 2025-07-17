<?php

namespace WebGenerator;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

use WebGenerator\GeneratorUtilities;
use WebGenerator\GeneratorAdminActions;

class GeneratorPostSettings {

  /**
   * Initialize post settings
   *
   * @return void
   */
  public function init() {
    add_filter('post_row_actions', array( $this, 'add_custom_post_row_action' ), 10, 2);
    add_filter('page_row_actions', array( $this, 'add_custom_post_row_action' ), 10, 2);
    add_action('admin_init', array( $this, 'add_duplicate_post_setting' ));

    add_action( 'admin_init', array( $this, "post_settings_init" ) );
    add_action( 'admin_notices', array( $this, "post_settings_messages" ));
  }

  /**
   * Execute post settings initialization
   *
   * @return void
   */
  public function post_settings_init() {
    if( GeneratorAdminActions::validate_action( 'diva_duplicate_post' ) && isset( $_GET['diva_post_id'] ) ) {
      GeneratorUtilities::duplicate_post( $_GET['diva_post_id']);
      GeneratorAdminActions::redirect_action( 'diva_duplicate_post' );
      exit;
    }
  }

  /**
   * Display a message after duplicating a post
   *
   * @return void
   */
  public function post_settings_messages() {
    GeneratorAdminActions::display_action_message( "diva_duplicate_post", "Post has been duplicated!" );
  }

  /**
   * Add custom row action to posts, duplicate post feature
   *
   * @param array $actions
   * @param \WP_Post $post
   * @return array
   */
  public function add_custom_post_row_action( $actions, $post ) {
    if (current_user_can('edit_posts') && in_array($post->post_type, get_post_types(['public' => true], 'names'))) {
      $custom_link = '<a href="' . GeneratorAdminActions::generate_action_url('diva_duplicate_post') . "&diva_post_id={$post->ID}" . '">Duplicate</a>';
      $actions['diva_duplicate'] = '<a href="' . esc_url($custom_link) . '" title="Duplicate this item" rel="permalink">Duplicate</a>';
    }
    return $actions;
  }

  /**
   * Add duplicate post setting to all public post types
   *
   * @return void
   */
  public function add_duplicate_post_setting() {
    $post_types = get_post_types(['public' => true], 'names');
    foreach ($post_types as $post_type) {
      add_filter("{$post_type}_row_actions", array( $this, 'add_custom_post_row_action' ), 10, 2);
    }
  }
}