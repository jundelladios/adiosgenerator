<?php

if ( ! function_exists( 'adiosgenerator_epanel_save_data' ) ):

  function adiosgenerator_epanel_save_data( $source ) {

    global $options, $shortname;

    et_core_nonce_verified_previously();

    if ( ! current_user_can( 'edit_theme_options' ) ) {
      die('-1');
    }

    if ( defined( 'ET_BUILDER_DIR' ) && file_exists( ET_BUILDER_DIR . 'class-et-builder-settings.php' ) ) {
      require_once ET_BUILDER_DIR . 'class-et-builder-settings.php';
      et_builder_settings_init();
    }

    // load theme settings array
    et_load_core_options();

    /** This filter is documented in {@see et_build_epanel()} */
    $options = apply_filters( 'et_epanel_layout_data', $options );

    if ( isset($_POST['action']) ) {
      do_action( 'et_epanel_changing_options' );

      $epanel = isset( $_GET['page'] ) ? $_GET['page'] : basename( __FILE__ );
      $redirect_url = esc_url_raw( add_query_arg( 'page', $epanel, admin_url( 'admin.php' ) ) );

      if ( 'save_epanel' === $_POST['action'] || 'save_epanel_temp' === $_POST['action'] ) {

        $updates_options = array();

        if ( is_array( get_site_option( 'et_automatic_updates_options' ) ) ) {
          $updates_options = get_option( 'et_automatic_updates_options' );
        }

        // Network Admins can edit options like Super Admins but content will be filtered
        // (eg `>` in custom CSS would be encoded to `&gt;`) so we have to disable kses filtering
        // while saving epanel options.
        $skip_kses = ! current_user_can( 'unfiltered_html' );
        if ( $skip_kses ) {
          kses_remove_filters();
        }

        $shortname .= 'save_epanel_temp' === $_POST['action'] ? '_' . get_current_user_id() : '';
        foreach ( $options as $value ) {
          $et_option_name   = $et_option_new_value = false;
          $is_builder_field = isset( $value['is_builder_field'] ) && $value['is_builder_field'];

          if ( isset( $value['id'] ) ) {
            $et_option_name = $value['id'];

            if ( isset( $_POST[ $value['id'] ] ) || 'checkbox_list' === $value['type'] ) {
              if ( in_array( $value['type'], array( 'text', 'textlimit', 'password' ) ) ) {

                if( 'password' === $value['type'] && _et_epanel_password_mask() === $_POST[$et_option_name] ) {
                  // The password was not modified so no need to update it
                  continue;
                }

                if ( isset( $value['validation_type'] ) ) {
                  // saves the value as integer
                  if ( 'number' === $value['validation_type'] ) {
                    $et_option_new_value = intval( stripslashes( $_POST[$value['id']] ) );
                  }

                  // makes sure the option is a url
                  if ( 'url' === $value['validation_type'] ) {
                    $et_option_new_value = esc_url_raw( stripslashes( $_POST[ $value['id'] ] ) );
                  }

                  // option is a date format
                  if ( 'date_format' === $value['validation_type'] ) {
                    $et_option_new_value = sanitize_option( 'date_format', $_POST[ $value['id'] ] );
                  }

                  /*
                  * html is not allowed
                  * wp_strip_all_tags can't be used here, because it returns trimmed text, some options need spaces ( e.g 'character to separate BlogName and Post title' option )
                  */
                  if ( 'nohtml' === $value['validation_type'] ) {
                    $et_option_new_value = stripslashes( wp_filter_nohtml_kses( $_POST[$value['id']] ) );
                  }
                  if ( 'apikey' === $value['validation_type'] ) {
                    $et_option_new_value = stripslashes( sanitize_text_field( $_POST[ $value['id'] ]  ) );
                  }
                } else {
                  // use html allowed for posts if the validation type isn't provided
                  $et_option_new_value = wp_kses_post( stripslashes( $_POST[ $value['id'] ] ) );
                }

              } elseif ( 'select' === $value['type'] ) {

                // select boxes that list pages / categories should save page/category ID ( as integer )
                if ( isset( $value['et_array_for'] ) && in_array( $value['et_array_for'], array( 'pages', 'categories' ) ) ) {
                  $et_option_new_value = intval( stripslashes( $_POST[$value['id']] ) );
                } else { // html is not allowed in select boxes
                  $et_option_new_value = sanitize_text_field( stripslashes( $_POST[$value['id']] ) );
                }

              } elseif ( in_array( $value['type'], array( 'checkbox', 'checkbox2' ) ) ) {

                // saves 'on' value to the database, if the option is enabled
                $et_option_new_value = 'on';

              } elseif ( 'upload' === $value['type'] ) {

                // makes sure the option is a url
                $et_option_new_value = esc_url_raw( stripslashes( $_POST[ $value['id'] ] ) );

              } elseif ( in_array( $value['type'], array( 'textcolorpopup', 'et_color_palette' ) ) ) {

                // the color value
                $et_option_new_value = sanitize_text_field( stripslashes( $_POST[$value['id']] ) );

              } elseif ( 'textarea' === $value['type'] ) {

                if ( isset( $value['validation_type'] ) ) {
                  // html is not allowed
                  if ( 'nohtml' === $value['validation_type'] ) {
                    if ( $value['id'] === ( $shortname . '_custom_css' ) ) {
                      // save custom css into wp custom css option if supported
                      // fallback to legacy system otherwise
                      if ( function_exists( 'wp_update_custom_css_post' ) ) {
                        // Data sent via AJAX is automatically escaped by browser, thus it needs
                        // to be unslashed befor being saved into custom CSS post
                        wp_update_custom_css_post( wp_unslash( wp_strip_all_tags( $_POST[ $value['id'] ] ) ) );
                      } else {
                        // don't strip slashes from custom css, it should be possible to use \ for icon fonts
                        $et_option_new_value = wp_strip_all_tags( $_POST[ $value['id'] ] );
                      }
                    } else {
                      $et_option_new_value = wp_strip_all_tags( stripslashes( $_POST[ $value['id'] ] ) );
                    }
                  }
                } else {
                  if ( current_user_can( 'edit_theme_options' ) ) {
                    $et_option_new_value = stripslashes( $_POST[ $value['id'] ] );
                  } else {
                    $et_option_new_value = stripslashes( wp_filter_post_kses( addslashes( $_POST[ $value['id'] ] ) ) ); // wp_filter_post_kses() expects slashed value
                  }
                }

              } elseif ( 'checkboxes' === $value['type'] ) {

                if ( isset( $value['value_sanitize_function'] ) && 'sanitize_text_field' === $value['value_sanitize_function'] ) {
                  // strings
                  $et_option_new_value = array_map( 'sanitize_text_field', stripslashes_deep( $_POST[ $value['id'] ] ) );
                } else {
                  // saves categories / pages IDs
                  $et_option_new_value = array_map( 'intval', stripslashes_deep( $_POST[ $value['id'] ] ) );
                }

              } elseif ( 'different_checkboxes' === $value['type'] ) {

                // saves 'author/date/categories/comments' options
                $et_option_new_value = array_map( 'sanitize_text_field', array_map( 'wp_strip_all_tags', stripslashes_deep( $_POST[$value['id']] ) ) );

              } elseif ( 'checkbox_list' === $value['type'] ) {
                // saves array of: 'value' => 'on' or 'off'
                $raw_checked_options = isset( $_POST[ $value['id'] ] ) ? stripslashes_deep( $_POST[ $value['id'] ] ) : array();
                $checkbox_options    = $value['options'];

                if ( is_callable( $checkbox_options ) ) {
                  // @phpcs:ignore Generic.PHP.ForbiddenFunctions.Found
                  $checkbox_options = call_user_func( $checkbox_options );
                }

                $allowed_values = array_values( $checkbox_options );

                if ( isset( $value['et_save_values'] ) && $value['et_save_values'] ) {
                  $allowed_values = array_keys( $checkbox_options );
                }

                $et_option_new_value = array();

                foreach ( $allowed_values as $allowed_value ) {
                  $et_option_new_value[ $allowed_value ] = in_array( $allowed_value, $raw_checked_options ) ? 'on' : 'off';
                }
              }
            } else {
              if ( in_array( $value['type'], array( 'checkbox', 'checkbox2' ) ) ) {
                $et_option_new_value = $is_builder_field ? 'off' : 'false';
              } else if ( 'different_checkboxes' === $value['type'] ) {
                $et_option_new_value = array();
              } else {
                et_delete_option( $value['id'] );
              }
            }

            if ( false !== $et_option_name && false !== $et_option_new_value ) {
              $is_new_global_setting    = false;
              $global_setting_main_name = $global_setting_sub_name = '';

              if ( isset( $value['is_global'] ) && $value['is_global'] ) {
                $is_new_global_setting    = true;
                $global_setting_main_name = isset( $value['main_setting_name'] ) ? sanitize_text_field( $value['main_setting_name'] ) : '';
                $global_setting_sub_name  = isset( $value['sub_setting_name'] ) ? sanitize_text_field( $value['sub_setting_name'] ) : '';
              }

              /**
               * Fires before updating an ePanel option in the database.
               *
               * @param string $et_option_name      The option name/id.
               * @param string $et_new_option_value The new option value.
               */
              do_action( 'et_epanel_update_option', $et_option_name, $et_option_new_value );

              if ( 'et_automatic_updates_options' === $global_setting_main_name && 'save_epanel_temp' !== $_POST['action'] ) {
                $updates_options[ $global_setting_sub_name ] = $et_option_new_value;

                update_site_option( $global_setting_main_name, $updates_options );
              } else {
                et_update_option( $et_option_name, $et_option_new_value, $is_new_global_setting, $global_setting_main_name, $global_setting_sub_name );
              }
            }
          }
        }

        if ( $skip_kses ) {
          // Enable kses filters again
          kses_init_filters();
        }

        $redirect_url = add_query_arg( 'saved', 'true', $redirect_url );

        die('1');

      } 
      
      else if ( 'reset' === $_POST['action'] ) {

        foreach ($options as $value) {
          if ( isset($value['id']) ) {
            et_delete_option( $value['id'] );
            if ( isset( $value['std'] ) ) {
              et_update_option( $value['id'], $value['std'] );
            }
          }
        }

        // Reset Google Maps API Key
        update_option( 'et_google_api_settings', array() );

        // Resets WordPress custom CSS which is synced with Options Custom CSS as of WP 4.7
        if ( function_exists( 'wp_get_custom_css' ) ) {
          wp_update_custom_css_post('');
          set_theme_mod( 'et_pb_css_synced', 'no' );
        }

        $redirect_url = add_query_arg( 'reset', 'true', $redirect_url );

        die('1');
      }
    }
  }
endif;