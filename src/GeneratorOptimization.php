<?php

namespace WebGenerator;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

class GeneratorOptimization {

  /**
   * Initialize optimization feature
   *
   * @return void
   */
  public function init() {
    
    add_action( 'wp_head', array( $this, "divi_palletes_css_variables" ), 999 );
    add_action( 'wp_head', array( $this, "process_preload_medias" ), 999 );

    add_action( 'wp_enqueue_scripts', array( $this, 'lazybackgroundjs' ) );
    
    // buffer lazy process
    add_action('the_content', array( $this, "buffer_optimize_output" ), 100, 20 );

    // defer styles
    add_filter( 'style_loader_tag', array( $this, "defer_styles" ), 999, 200 );
  }

  public function lazybackgroundjs() {
    if( $this->is_optimize() ) {
      wp_enqueue_script(
        'adiosgenerator-lazybackground',
        constant('ADIOSGENERATOR_PLUGIN_URI') . 'assets/lazybackground.js',
        [ 'jquery' ],
        null,
        true
      );
    }
  }

  /**
   * Buffer optimize output
   *
   * @param string $content
   * @return void
   */
  public function buffer_optimize_output( $content ) {
    $content = $this->disable_lazyload_for_images( $content );
    $content = $this->no_lazy_first_two_section( $content );
    return $content;
  }
  
  /**
   * Disable lazyload for first two sections
   *
   * @param string $content
   * @return void
   */
  private function no_lazy_first_two_section( $content ) {
    $pattern = '/(<div\b[^>]*class="[^"]*\bet_pb_section\b[^"]*")/';
    $count = 0;

    $content = preg_replace_callback($pattern, function($matches) use (&$count) {
      if ($count < 2) {
        $count++;
        return preg_replace('/class="([^"]*)"/', 'class="$1 loaded"', $matches[0]);
      }
      return $matches[0];
    }, $content);

    return $content;
  }

  /**
   * Disable lazyload on images
   *
   * @param string $content
   * @return void
   */
  private function disable_lazyload_for_images( $content ) {
    if( !$this->is_optimize() ) {
      return $content;
    }

    // disable lazyload for specific images
    $excludes = get_posts(array(
      "post_type" => "attachment",
      "posts_per_page" => -1,
      "meta_key" => "adiosgenerator_disable_lazyload",
      "meta_value" => 1,
      "compare" => "="
    ));

    $excluded_urls  = array();
    foreach( $excludes as $exl ) {
      $excluded_urls[] = $exl->guid;
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

        if( true === in_array( $current_src, $excluded_urls ) ) {
          $img_match_new = preg_replace( '/<img\s/i', '<img data-no-lazy="1" data-cfasync="false" data-cf-no-optimize ', $img_match, 1 );
          // remove loading attribute
          $img_match_new = preg_replace('/\sloading=("|\')[^"\']*("|\')/i', '', $img_match_new);
          // replace eager loading
          $img_match_new = preg_replace( '/<img\s/i', '<img loading="eager" ', $img_match_new, 1 );
          // empty alt
          if (preg_match('/<img[^>]*\salt=["\']\s*["\'][^>]*>/i', $img_match_new)) {
            $img_match_new = preg_replace('/\salt=["\'][^"\']*["\']/i', ' alt="' . esc_attr( $alt ) . '" ', $img_match_new);
          }

          $content = str_replace( $img_match, $img_match_new, $content );
        }
      }
    }

    return $content;
  }

  /**
   * Defer css setter
   *
   * @param [type] $link
   * @return void
   */
  public function defer_style_setter( $link ) {
    return '<link href="' . esc_url($link) . '"  rel="preload" as="style" onload="this.onload=null;this.rel=\'stylesheet\';" />';
  }

  /**
   * Handles defer style of css links
   *
   * @param string $tag
   * @param string $handle
   * @return void
   */
  public function defer_styles( $tag, $handle ) {
    if( !is_user_logged_in() && ( is_page() || is_single() || is_category() || is_tag() || is_home() ) ) {
      preg_match("/href=['\"]([^'\"]+)['\"]/", $tag, $matches);
      if (!empty($matches[1])) {
        $tag = $this->defer_style_setter( $matches[1] );
      }
    }
    return $tag;
  }

  /**
   * Check if optimization is applicable
   *
   * @return boolean
   */
  public function is_optimize() {
    include_once(ABSPATH . 'wp-includes/pluggable.php');
    return ( is_user_logged_in() && isset( $_GET['et_fb'] ) ) || current_user_can( 'edit_posts' ) ? false : true;
  }

  /**
   * Embed scripts and global styles in header
   *
   * @return void
   */
  public function divi_palletes_css_variables() {

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
    div.et_pb_section.et_pb_with_background:not(.loaded),
    div.et_pb_section:not(.loaded) .et_pb_with_background,
	  div.et_pb_section:not(.loaded) .et_pb_slide:not(.et-pb-active-slide)
    {
    ";
    $custom_css .= "background-image: unset!important;";
    $custom_css .= "}";
    endif;


    $custom_css .= "</style>";
    echo $custom_css;
  }

  /**
   * Processing media preload feature
   *
   * @param string $content
   * @return void
   */
  public function process_preload_medias() {
    
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
      $is_srcset = get_post_meta($prel->ID, "adiosgenerator_preload_srcset", true);
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

      $href = $prel->guid;

      $srcset = wp_get_attachment_image_srcset( $prel->ID, "full" );
      $srcset = $srcset ? " srcset=\"{$srcset}\"" : "";

      $sizes  = wp_get_attachment_image_sizes( $prel->ID, 'full' );
      $sizes = $sizes ? " sizes=\"{$sizes}\"" : "";

      $srcsetsizes = $is_srcset ? " {$srcset} {$sizes} " : "";

      $preload_lists .= " <link rel=\"preload\" {$aspreload_as} href=\"{$href}\" type=\"{$mime}\" {$priority} {$srcsetsizes} /> ";
    }

    echo $preload_lists;
  }
}