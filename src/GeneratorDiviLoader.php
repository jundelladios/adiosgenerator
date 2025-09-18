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

    // Check if Divi theme is active
    $theme = wp_get_theme();
    if (!($theme->get('Name') === 'Divi' || $theme->get_template() === 'Divi')) {
      return;
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
