<?php

namespace WebGenerator;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

/**
 * Utility class to ensure Divi classes are loaded for scheduled actions
 * This is necessary because scheduled actions run outside the normal WordPress context
 * where Divi classes might not be loaded yet.
 */
class GeneratorDiviLoader {

  /**
   * Ensure Divi classes are loaded for scheduled actions
   * This is necessary because scheduled actions run outside the normal WordPress context
   * where Divi classes might not be loaded yet.
   */
  public static function ensure_divi_classes_loaded() {
    // Check if ET_Builder_Value class already exists
    if (class_exists('ET_Builder_Value')) {
      return;
    }

    // Ensure WordPress environment is properly set up for CLI context
    if (defined('WP_CLI') && WP_CLI) {
      // Set up WordPress environment variables that might be missing in CLI
      if (!isset($_SERVER['HTTP_HOST'])) {
        $_SERVER['HTTP_HOST'] = parse_url(home_url(), PHP_URL_HOST);
      }
      if (!isset($_SERVER['REQUEST_URI'])) {
        $_SERVER['REQUEST_URI'] = '/';
      }
      if (!isset($_SERVER['SERVER_NAME'])) {
        $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];
      }
      
      // Ensure current user is set for CLI context
      if (!current_user_can('manage_options')) {
        // Set a user with admin capabilities for CLI context
        $admin_users = get_users(array('role' => 'administrator', 'number' => 1));
        if (!empty($admin_users)) {
          wp_set_current_user($admin_users[0]->ID);
        }
      }
    }

    // Check if Divi theme is active
    $theme = wp_get_theme();
    if (!($theme->get('Name') === 'Divi' || $theme->get_template() === 'Divi')) {
      return;
    }

    // Define ET_BUILDER_DIR_RESOLVED_PATH constant first before loading any Divi files
    // This prevents "Undefined constant" errors when Divi WooCommerce modules try to use this constant
    if (!defined('ET_BUILDER_DIR_RESOLVED_PATH')) {
      define('ET_BUILDER_DIR_RESOLVED_PATH', get_template_directory() . '/includes/builder');
    }

    // Load Divi functions.php to define all necessary constants
    $divi_functions_file = get_template_directory() . '/includes/builder/functions.php';
    if (file_exists($divi_functions_file)) {
      require_once $divi_functions_file;
    }

    // Load Divi epanel functions for et_update_option and et_get_option
    $divi_epanel_functions_file = get_template_directory() . '/epanel/custom_functions.php';
    if (file_exists($divi_epanel_functions_file)) {
      require_once $divi_epanel_functions_file;
    }

    // Define ET_BUILDER_DIR if not already defined
    if (!defined('ET_BUILDER_DIR')) {
      define('ET_BUILDER_DIR', get_template_directory() . '/includes/builder/');
    }

    // Load the ET_Builder_Value class file
    $builder_value_file = ET_BUILDER_DIR . 'class-et-builder-value.php';
    if (file_exists($builder_value_file)) {
      require_once $builder_value_file;
    }

    // Load other essential Divi builder classes that might be needed
    $essential_files = [
      'class-et-builder-element.php',
      'class-et-builder-settings.php',
      'feature/dynamic-content.php'
    ];

    foreach ($essential_files as $file) {
      $file_path = ET_BUILDER_DIR . $file;
      if (file_exists($file_path)) {
        require_once $file_path;
      }
    }

    // Load ET_Core classes if not already loaded
    if (!class_exists('ET_Core_PageResource')) {
      $core_dir = get_template_directory() . '/includes/core/';
      $core_files = [
        'class-et-core-page-resource.php'
      ];
      
      foreach ($core_files as $file) {
        $file_path = $core_dir . $file;
        if (file_exists($file_path)) {
          require_once $file_path;
        }
      }
    }

    // Load ET_Builder_Module_Social_Media_Follow_Item if not already loaded
    if (!class_exists('ET_Builder_Module_Social_Media_Follow_Item')) {
      $module_file = ET_BUILDER_DIR . 'module/ET_Builder_Module_Social_Media_Follow_Item.php';
      if (file_exists($module_file)) {
        require_once $module_file;
      }
    }
  }
}
