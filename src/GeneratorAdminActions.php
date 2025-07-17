<?php

namespace WebGenerator;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

class GeneratorAdminActions {

  /**
   * Validate action from url
   *
   * @param string $action
   * @return boolean
   */
  public static function validate_action( $action ) {
    if (isset($_GET['diva_action']) && $_GET['diva_action'] === $action) {
      if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'diva_nonce')) {
        wp_die('Security check failed');
      }
      return true;
    }
    return false;
  }

  /**
   * Generate action url for admin bar
   *
   * @param string $action
   * @return string
   */
  public static function generate_action_url( $action ) {
    return admin_url( '?diva_action=' . $action . '&_wpnonce=' . wp_create_nonce( 'diva_nonce' ) . '&diva_redirect=' . urlencode( home_url( add_query_arg( null, null ) ) ) );;  
  }


  /**
   * Redirect action after validation
   *
   * @param string $action
   * @return void
   */
  public static function redirect_action( $action ) {
    set_transient($action . get_current_user_id(), true, 30);
    wp_redirect( isset( $_GET['diva_redirect'] ) ? $_GET['diva_redirect'] : admin_url() );
  }


  /**
   * Display action message
   *
   * @param string $action
   * @param string $message
   * @return void
   */
  public static function display_action_message( $action, $message ) {
    if (get_transient($action . get_current_user_id())):
    ?>
    <div class="notice notice-success is-dismissible">
      <p><?php echo $message; ?></p>
    </div>
    <?php
    delete_transient($action . get_current_user_id());
    endif;
  }
}