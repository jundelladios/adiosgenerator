<?php

namespace WebGenerator;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

/**
 * 
 * Class to handle contents replacements upon sync
 */
class GeneratorOptimization {

  public function init() {
    add_action( 'wp_enqueue_scripts', array( $this, "remove_unecessary_styles" ), 100 );
    remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
    
    add_action( 'wp_head', array( $this, "divi_palletes_css_variables" ), 999 );
    add_action( 'wp_footer', array( $this, "divi_lazy_images" ), 999 );

    // attachment optimization settings
    add_filter( 'attachment_fields_to_edit',  array( $this, "media_settings" ), 10, 2 );
    add_filter( "attachment_fields_to_save", array( $this, "media_settings_save" ), null, 2); 
    
    // breeze buffer cache process
    add_filter("breeze_cache_buffer_before_processing", array( $this, "breeze_cache_buffer_process" ), 10, 2 );

    // defer styles
    add_filter( 'style_loader_tag', array( $this, "defer_styles" ), 100, 2 );
  }

  public function defer_style_setter( $link ) {
    return '<link href="' . esc_url($link) . '"  rel="preload" as="style" onload="this.onload=null;this.rel=\'stylesheet\';" />';
  }

  public function defer_styles( $tag, $handle ) {
    if(is_user_logged_in()) {
      return $tag;
    }
    preg_match("/href=['\"]([^'\"]+)['\"]/", $tag, $matches);
    if (!empty($matches[1])) {
      $tag = $this->defer_style_setter( $matches[1] );
    }
    return $tag;
  }

  // if not handled by style_loader_tag then modify the buffer
  public function defer_css_style_assets( $buffer ) {
    $buffer = preg_replace_callback(
        '/<link\s+[^>]*rel=["\']stylesheet["\'][^>]*>/i',
        function($matches) {
            $original_tag = $matches[0];
            if (preg_match('/href=["\']([^"\']+)["\']/', $original_tag, $href_match)) {
                $href = $href_match[1];
                $new_tag = $this->defer_style_setter( $href );
                return $new_tag;
            }
            return $original_tag;
        },
        $buffer
    );
    return $buffer;
  }
  

  /**
   * remove guttenberg css this is not necessary
   */
  public function remove_unecessary_styles() {
    wp_deregister_style( 'wc-blocks-style' );
    wp_dequeue_style( 'wp-block-library' );
    wp_dequeue_style( 'wp-block-library-theme' );
    wp_dequeue_style( 'wc-blocks-style' );
  }

  /**
   * check if optimization is enabled
   */
  public function is_optimize() {
    include_once(ABSPATH . 'wp-includes/pluggable.php');
    return ( is_user_logged_in() && isset( $_GET['et_fb'] ) ) || current_user_can( 'edit_posts' ) ? false : true;
  }

  /**
   * style variables and optimization
   * ensure at the bottom to override previous styles of the head to avoid conflict
   */
  public function divi_palletes_css_variables() {
    if( !function_exists( 'et_get_option' ) ) {
      return false;
    }

    $accent_color = et_get_option( "accent_color" );
    $secondary_accent_color = et_get_option( "secondary_accent_color" );
    $color_palletes = et_get_option( "divi_color_palette" );
    
    $pallete_lists = explode( "|", $color_palletes );
    
    $custom_css = "<style type=\"text/css\" id=\"wp-generator-custom-css-optimization\">";
    
    // css for global variables
    $custom_css .= ":root {";
    $custom_css .= "--divi-primary-color: {$accent_color};";
    $custom_css .= "--divi-secondary-color: {$secondary_accent_color};";
    $pallete_index = 1;
    foreach( $pallete_lists as $color) {
      $custom_css .= "--divi-color-{$pallete_index}: {$color};";
      $pallete_index++;
    }
    $custom_css .= "}";

    // css for lazy backgrounds
    if( $this->is_optimize()):
    $custom_css .= "
    .et_pb_slider:not(.no-lazyload, .entered) .et_pb_slide,
    .et_pb_slider .et_pb_slide:not(.et-pb-active-slide), 
    div.et_pb_section.et_pb_with_background:not(.no-lazyload, .entered),
    div.et_pb_row.et_pb_with_background:not(.no-lazyload, .entered),
    div.et_pb_column.et_pb_with_background:not(.no-lazyload, .entered),
    div.et_pb_module.et_pb_with_background:not(.no-lazyload, .entered)
    {
    ";
    $custom_css .= "background-image: unset!important;";
    $custom_css .= "}";
    endif;


    $custom_css .= "</style>";
    echo $custom_css;
  }

  // breeze divi lazybackground and images scripts
  public function divi_lazy_images() {
    $custom_js = "<script type=\"text/javascript\" id=\"wp-generator-custom-js-optimization\">";

    if( $this->is_optimize()):
    $custom_js .= '
    const AdiosGeneratorLazyLoadDiviBackgroundInstance = new LazyLoad({
        elements_selector: ".et_pb_slider, .et_pb_with_background",
        threshold: 300
    });
    
    const AdiosGeneratorLazyloadImagesInstance = new LazyLoad({
        elements_selector: ".br-lazy",
        data_src: "breeze",
        data_srcset: "brsrcset",
        data_sizes: "brsizes",
        class_loaded: "br-loaded",
        threshold: 300
    });
    ';
    endif;

    $custom_js .= "</script>";
    echo $custom_js;
  }

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
        "label" => "LCP: Prioritize Background Image",
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
      )
    );
  }
  
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

  public function media_settings_save( $post, $attachment ) {
    $fields = $this->attachment_fields();
    foreach( $fields as $field ) {
      $value = isset( $attachment[$field['name']] ) ? $attachment[$field['name']] : "0";
      update_post_meta($post['ID'], $field['name'], $value);
    }
    return $post;
  }

  
  public function breeze_cache_buffer_process( $buffer ) {
    $buffer = $this->process_preload_medias( $buffer );
    $buffer = $this->breeze_cache_nolazyload( $buffer );
    $buffer = $this->defer_css_style_assets( $buffer );
    $buffer = apply_filters( 'breeze_cdn_content_return', $buffer );
    return $buffer;
  }

  public function process_preload_medias( $content ) {
    
    // get prioritize urls
    $preloads = get_posts(array(
      "post_type" => "attachment",
      "posts_per_page" => -1,
      "meta_key" => "adiosgenerator_prioritize_background",
      "meta_value" => range(1, 9),
      "compare" => "IN"
    ));

    $preload_lists = "";
    foreach( $preloads as $prel ) {
      $mime = $prel->post_mime_type;
      $preload_as = explode( "/", $mime );
      $aspreload_as = isset( $preload_as[0] ) ? "as=\"{$preload_as[0]}\"" : "";

      $preload_type = get_post_meta($prel->ID, "adiosgenerator_prioritize_background", true);
      $priority = "";

      if( in_array( $preload_type, array( "1", "3", "5" ) )) {
        $priority .= " fetchpriority=\"high\" ";
      }
      if( in_array( $preload_type, array( "2", "4", "6" ) )) {
        $priority .= " fetchpriority=\"low\" ";
      }
      if( in_array( $preload_type, array( "1", "2", "8" ) )) {
        $priority .= " media=\"(min-width: 768px)\" ";
      }
      if( in_array( $preload_type, array( "3", "4", "9" ) )) {
        $priority .= " media=\"(max-width: 768px)\" ";
      }

      $href = $this->cdn_url( $prel->guid );

      $srcset = wp_get_attachment_image_srcset( $prel->ID, "full" );
      $srcset = $srcset ? " srcset=\"{$srcset}\"" : "";

      $sizes  = wp_get_attachment_image_sizes( $prel->ID, 'full' );
      $sizes = $sizes ? " sizes=\"{$sizes}\"" : "";

      $preload_lists .= " <link rel=\"preload\" {$aspreload_as} href=\"{$href}\" type=\"{$mime}\" {$priority} {$srcset} {$sizes} /> ";
    }

    // insert priorities in head tag
    $content = str_replace( "</head>", $preload_lists . "</head>", $content );

    return $content;
  }


  public function cdn_url( $url ) {
    if ( class_exists( 'Breeze_Options_Reader' ) && !empty( \Breeze_Options_Reader::get_option_value( 'cdn-active' )) && !empty( \Breeze_Options_Reader::get_option_value( 'cdn-url' ) ) ) {
      $site_url = get_site_url();
      $cdn_url = \Breeze_Options_Reader::get_option_value( 'cdn-url' );
      return str_replace($site_url . '/wp-content/', $cdn_url . '/wp-content/', $url);
    }
    return $url;
  }

  public function breeze_cache_nolazyload( $html ) {
    $content = $html;
    $excludes = get_posts(array(
      "post_type" => "attachment",
      "posts_per_page" => -1,
      "meta_key" => "adiosgenerator_disable_lazyload",
      "meta_value" => 1,
      "compare" => "="
    ));

    $srcs = array();
    foreach( $excludes as $exl ) {
      $excludeURL = $this->cdn_url($exl->guid);
      $srcs[] = $this->cdn_url($exl->guid);
    }

    preg_match_all( '/<img[^>]+>/i', $content, $img_matches );

    $img_matches[0] = array_filter(
      $img_matches[0],
      function ( $tag ) {
        return strpos( $tag, '\\' ) === false;
      }
    );

    if ( ! empty( $img_matches[0] ) ) {
      foreach ( $img_matches[0] as $img_match ) {
        preg_match( '/src=(?:"|\')(.+?)(?:"|\')/', $img_match, $src_value );
        $current_src = ! empty( $src_value[1] ) ? $src_value[1] : '';

        preg_match('/alt=["\']([^"\']*)["\']/', $img_match, $alt_match);
        $alt = isset($alt_match[1]) ? $alt_match[1] : '';

        preg_match('/title=["\']([^"\']*)["\']/', $img_match, $title_match);
        $alt = empty( $alt ) && isset($title_match[1]) ? $title_match[1] : $alt;

        preg_match('/width=["\']([^"\']*)["\']/', $img_match, $width_match);
        $width = isset($width_match[1]) ? $width_match[1] : '';

        preg_match('/width=["\']([^"\']*)["\']/', $img_match, $height_match);
        $height = isset($height_match[1]) ? $height_match[1] : '';

        if( true === in_array( $current_src, $srcs ) ) {
          $img_match_new = preg_replace( '/<img\s/i', '<img data-no-lazy="1" data-cfasync="false" data-cf-no-optimize ', $img_match, 1 );
          $img_match_new = preg_replace('/\sloading=("|\')[^"\']*("|\')/i', '', $img_match_new);
          // empty alt
          if (preg_match('/<img[^>]*\salt=["\']\s*["\'][^>]*>/i', $img_match_new)) {
            $img_match_new = preg_replace('/\salt=["\'][^"\']*["\']/i', ' alt="' . esc_attr( $alt ) . '" ', $img_match_new);
          }

          $content = str_replace( $img_match, $img_match_new, $content );
        } else {
          $img_match_new = preg_replace('/\salt=["\'][^"\']*["\']/i', ' alt="' . esc_attr( $alt ) . '" ', $img_match);
          $img_match_new = $img_match_new . '<noscript><img data-no-lazy="1" src="' . esc_url($current_src) . '" alt="' . esc_attr( $alt ) . '" width="' . esc_attr( $width ) . '"  height="' . esc_attr( $height ) . '" /></noscript>';
          $content = str_replace( $img_match, $img_match_new, $content );
        }
        
      }
    }

    return $content;
  }
}