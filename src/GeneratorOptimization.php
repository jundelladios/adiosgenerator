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
    add_action( 'wp_head', array( $this, "diva_launch_wp_head_css" ), 999 );
    add_action( 'wp_footer', array( $this, 'diva_launch_optimization' ), 9999 );

    // Remove wp-block-library-theme-inline-css and global-styles-inline-css from enqueued styles
    add_action('wp_enqueue_scripts', function() {
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
        wp_dequeue_style('global-styles'); // if WooCommerce
    }, 20);

    // buffer content
    add_action('template_redirect', array( $this, "buffer_optimize_output_content" ), 100, 20 );

    // initialize carbon settings for optimizations
    $this->initialize_init_carbon_settings();
  }


  public function initialize_init_carbon_settings() {

    $settings = array(
      'diva_generator_force_eager_images',
      'diva_generator_force_lazy_images',
      'diva_generator_exclude_delay_scripts',
      'diva_generator_preload_lists_removal'
    );

    foreach( $settings as $setting ) {
      add_filter( $setting, function( $args = array() ) use( $setting ) {
        $globalsetting = carbon_get_theme_option( $setting );
        if ( !empty( $globalsetting ) ) {
          $globalargs = array_filter( array_map( 'trim', explode( "\n", $globalsetting ) ) );
          $args = array_merge( $args, $globalargs );
        }

        $post_id = get_the_ID();
        if( !is_null( $post_id )) {
          $postsetting = carbon_get_post_meta( $post_id, $setting );
          if( !empty( $postsetting )) {
            $postargs = array_filter( array_map( 'trim', explode( "\n", $postsetting ) ) );
            $args = array_merge( $args, $postargs );
          }
        }

        return $args;
      });
    }
  }

  public function diva_launch_optimization() {
    if( $this->is_optimize() ) {
      ob_start();
      ?>
      <script type="text/javascript" src="<?php echo constant('ADIOSGENERATOR_PLUGIN_URI') . 'assets/lazyload.min.js'; ?>" defer></script>
      <?php
      echo ob_get_clean();
    }
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
  public function diva_launch_wp_head_css() {

    $accent_color = et_get_option( "accent_color" );
    $secondary_accent_color = et_get_option( "secondary_accent_color" );
    $color_palletes = et_get_option( "divi_color_palette" );
    
    $pallete_lists = explode( "|", $color_palletes );
    ob_start();
    ?>
    <style type="text/css" id="diva-launch-css">
      :root {
        --divi-primary-color: <?php echo $accent_color ?? "#311b92"; ?>;
        --divi-secondary-color: <?php echo $secondary_accent_color ?? "#311b92"; ?>;
        <?php
        $pallete_index = 0;
        foreach( $pallete_lists as $color) {
          ?>
          --divi-color-<?php echo $pallete_index; ?>: <?php echo $color; ?>;
          <?php
          $pallete_index++;
        }
        ?>
      }

      <?php if( $this->is_optimize()): ?>
      div.et_pb_section.et_pb_with_background:not(.loaded),
      div.et_pb_section:not(.loaded) .et_pb_with_background,
      div.et_pb_section:not(.loaded) .et_pb_slide,
      div.et_pb_section.et_pb_with_background.no-lazyload,
      div.et_pb_section.no-lazyload .et_pb_slide:nth-child(1),
      div.et_pb_section.no-lazyload .et_pb_slide:not(.et-pb-active-slide) {
        background-image: unset!important;
      }
      div.et_pb_section.et_pb_with_background.no-lazyload {
        position: relative;
      }
      img.bg-image-replaced-atf {
        position: absolute;
        left: 50%;
        top: 50%;
        bottom: 0;
        transform: translate(-50%, -50%);
        left: 50%;
        width: 100%;
        object-fit: cover;
        z-index: 0;
        height: 100%!important;
      }
      <?php endif; ?>
    </style>
    <?php
    echo trim( preg_replace( '/\s+/', ' ', ob_get_clean() ) );
  }

  /**
   * Buffer output
   */
  public function buffer_optimize_output_content() {
    ob_start(array( $this, 'buffer_modify_final_output' ));
  }

  /**
   * Output buffer content modification for optimization
   *
   * @param mixed $content
   * @return void
   */
  public function buffer_modify_final_output( $content ) {
    if( !$this->is_optimize()) { return $content; }
    $content = apply_filters( 'diva_generator_before_process_content', $content );
    $content = $this->preconnect_third_parties( $content );
    $content = $this->force_loading_eager_images( $content );
    $content = $this->force_non_prio_images( $content );
    $content = $this->force_remove_preloading_mistakes( $content );

    $content = $this->force_opt_style_loader( $content );
    $content = $this->force_atf_lcp_background( $content );

    $content = $this->force_delay_javascripts( $content );
    $content = $this->lazyload_iframes_with_placeholders( $content );
    $content = $this->lazyload_img_with_placeholders( $content );

    $content = $this->google_fonts_optimization( $content );

    apply_filters( 'diva_generator_after_process_content', $content );
    return $content;
  }

  /**
   * Background images for ATF LCP
   *
   * @param [type] $content
   * @return void
   */
  public function force_atf_lcp_background( $content ) {
    
    // Get all HTML elements that have both 'et_pb_section' and 'no-lazyload' classes, in any order
    preg_match_all(
        '/<([a-zA-Z0-9]+)([^>]*\sclass=["\'][^"\'>]*\b(et_pb_section)\b[^"\'>]*\b(no-lazyload)\b[^"\'>]*["\'][^>]*)>/i',
        $content,
        $matches,
        PREG_OFFSET_CAPTURE
    );

    // You can now use $child_contents as needed, e.g. for further processing or debugging
    // $matches[0] will contain the full matched tags
    // $matches[1] will contain the tag names
    // $matches[2] will contain the attributes
    foreach( $matches[0] as $tags ) {
      foreach( $tags as $tag ) {
        // Get the class attribute value of the tag
        $class_value = "";
        if (preg_match('/class\s*=\s*([\'"])(.*?)\1/i', $tag, $class_match)) {
          $class_value = $class_match[2];
          // You can use $class_value as needed
        }

       // Get all child contents from this matched tag
       // Find the position of this tag in the content
       $tag_offset = strpos($content, $tag);
       if ($tag_offset !== false) {
         // Find the end of the opening tag
         $open_tag_end = $tag_offset + strlen($tag);
         // Try to get the tag name
         if (preg_match('/^<\s*([a-z0-9\-]+)/i', $tag, $tagname_match)) {
           $tag_name = $tagname_match[1];
           $close_tag = '</' . $tag_name . '>';
           // Find the closing tag position
           $close_tag_pos = stripos($content, $close_tag, $open_tag_end);
           if ($close_tag_pos !== false) {
             // Extract the child content between open_tag_end and close_tag_pos
             $child_content = substr($content, $open_tag_end, $close_tag_pos - $open_tag_end);
            
            // Find the first child element with class "et_pb_slide et_pb_slide_{number}" and get the slide number
            if (preg_match('/<div[^>]*class=["\'][^"\']*\bet_pb_slide\b[^"\']*\bet_pb_slide_(\d+)\b[^"\']*["\'][^>]*>/i', $child_content, $slide_match)) {
              $slide_tag = $slide_match[0];

              if (preg_match('/\bdata-slide-id=(["\'])(.*?)\1/i', $slide_tag, $matchslide_id)) {
                if( empty( $matchslide_id[2] )) { continue; }
                // Find <style> blocks in the content (typically in the header)
                if (preg_match_all('#<style[^>]*>(.*?)</style>#is', $content, $style_blocks)) {
                  foreach ($style_blocks[1] as $css) {
                    // Look for a CSS rule targeting the slide id as a class, e.g. .et_pb_slide_0 { background-image: url("..."); }
                    $pattern = '/\.' . preg_quote($matchslide_id[2], '/') . '\s*\{[^}]*background-image\s*:\s*url\((["\']?)(.*?)\1\)/is';
                    if (preg_match($pattern, $css, $bg_match)) {
                      $bg_url = $bg_match[2];

                      $image_id = attachment_url_to_postid( $bg_url );
                      if( $image_id ) {
                        $img_tag = wp_get_attachment_image( $image_id, 'full', false, [
                          'class' => 'bg-image-replaced-atf',
                          'loading' => 'eager',
                          'alt' => get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
                        ]);

                        $content = str_replace( $slide_tag, $slide_tag . $img_tag, $content );
                      }

                      break;
                    }
                  }
                }
              }

            }
          }
         }
       }

        // Check if $class_value contains a class like et_pb_section_{number}
        if (preg_match('/\bet_pb_section_(\d+)\b/', $class_value, $section_match)) {
          // $section_match[0] contains the full class name, e.g., et_pb_section_3
          $matched_class = $section_match[0];

          // Find the <style> blocks in the content (typically in the header)
          if (preg_match_all('#<style[^>]*>(.*?)</style>#is', $content, $style_blocks)) {
            foreach ($style_blocks[1] as $css) {
              // Look for a CSS rule targeting .$matched_class with background-image
              // e.g. .et_pb_section_3 { background-image: url("..."); }
              $pattern = '/\.' . preg_quote($matched_class, '/') . '\s*\{[^}]*background-image\s*:\s*url\((["\']?)(.*?)\1\)/is';
              if (preg_match($pattern, $css, $bg_match)) {
                $bg_url = $bg_match[2];
  
                $image_id = attachment_url_to_postid( $bg_url );
                if( $image_id ) {
                  $img_tag = wp_get_attachment_image( $image_id, 'full', false, [
                    'class' => 'bg-image-replaced-atf',
                    'loading' => 'eager',
                    'alt' => get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
                 ]);

                 $content = str_replace( $tag, $tag . $img_tag, $content );
                }

                break;
              }
            }
          }
        }

      }
    }
      
    return $content;
  }

  /**
   * Preconnect third parties
   * 
   */
  public function preconnect_third_parties( $content ) {
    preg_match_all('#<a\s[^>]*href=["\']([^"\']+)["\']#i', $content, $matches);
    $all_links = $matches[1] ?? [];
    $external_links = [];
    $site_host = parse_url(home_url(), PHP_URL_HOST);
    foreach ($all_links as $link) {
      $parsed = parse_url($link);
      if (isset($parsed['host']) && $parsed['host'] !== $site_host) {
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'];
        $home_url = $scheme . '://' . $host;
        $external_hosts[$home_url] = true; // use assoc array to prevent duplicates
      }
    }

    $unique_external_home_urls = array_keys($external_hosts);
    if (!empty($unique_external_home_urls)) {
      $thirdParties = "";
      foreach( $unique_external_home_urls as $url ) {
        $thirdParties .= "<link crossorigin data-web-generator-preconnect href=\"{$url}\" rel=\"preconnect\" />" . "\n";
      }
    }
    $content = str_replace('</title>', "</title>\n" . $thirdParties , $content);
    return $content;
  }

  public function script_lazy( $tag ) {
    $exclusions = apply_filters( 'diva_generator_exclude_delay_scripts', array(
      'lazyload.min.js'
    ));

    foreach ($exclusions as $keyword) {
      if (stripos($tag, $keyword) !== false) {
        return $tag;
      }
    }

    preg_match('#\s*type\s*=\s*(["\']).*?\1#i', $tag, $matches);
    if( !empty( $matches[0] )) {
      if( stripos( $matches[0], 'text/javascript' ) === false ) {
        return $tag;
      }

      $tag = str_replace( $matches[0], "", $tag);
    }

    $tag = preg_replace('#\s*async\s*=\s*(["\']).*?\1#i', '', $tag);
    $tag = preg_replace('#\s*defer\s*=\s*(["\']).*?\1#i', '', $tag);
    
    if(preg_match('#\s*src\s*=\s*(["\']).*?\1#i', $tag, $matches)) {
      $tag = str_replace('></script>', ' data-wpgenerator-defer defer></script>', $tag);
    }
    
    $tag = str_replace('<script', '<script type="wpGeneratorLazyScript" ', $tag);
    $tag = str_replace('src=', 'data-wpgenerator-src=', $tag);
    return $tag;
  }


  /**
   * delay javascripts with source replacement
   */
  public function force_delay_javascripts( $content ) {

    // for scripts with src
    $content = preg_replace_callback(
      '#<script\b([^>]*?)\bsrc=["\']([^"\']+)["\']([^>]*)>(.*?)</script>#is',
      function ($matches) {
        return $this->script_lazy( $matches[0] );
      },
      $content
    );


    // for inline scripts
    $content = preg_replace_callback(
    '#<script\b(?:(?!\bsrc\b)[^>])*>(.*?)</script>#is', 
    function ($matches) {
      return $this->script_lazy( $matches[0] );
    }, $content);

    return $content;
  }


  /**
   * Force loading images as eager
   *
   * @param [type] $content
   * @return void
   */
  public function force_loading_eager_images( $content ) {
    $image_filenames = apply_filters( 'diva_generator_force_eager_images', array() );
    if( !count( $image_filenames ) ) { return $content; }
    foreach ( $image_filenames as $filename ) {
      // Replace loading="lazy" with loading="eager" for specific image
      $content = preg_replace_callback(
        '#<img([^>]+?src=["\'][^"\']*' . preg_quote($filename, '#') . '[^"\']*["\'][^>]*)>#i',
        function( $matches ) {
          $img_tag = $matches[0];
          if ( strpos( $img_tag, 'loading=' ) !== false ) {
            $img_tag = preg_replace( '/loading=["\']lazy["\']/', 'loading="eager"', $img_tag );
          } else {
            $img_tag = str_replace( '<img', '<img loading="eager"', $img_tag );
          }
          return $img_tag;
        },
        $content
      );
    }

    return $content;
  }

  /**
   * Force non-prioritized images to lazyload
   *
   * @param [type] $content
   * @return void
   */
  public function force_non_prio_images( $content ) {
    $image_filenames = apply_filters( 'diva_generator_force_lazy_images', array() );
    if( !count( $image_filenames ) ) { return $content; }
    foreach ( $image_filenames as $filename ) {
      // force lazyload images
      $content = preg_replace_callback(
        '#<img([^>]+?src=["\'][^"\']*' . preg_quote($filename, '#') . '[^"\']*["\'][^>]*)>#i',
        function( $matches ) {
          $img_tag = $matches[0];
          if ( strpos( $img_tag, 'loading=' ) !== false ) {
            $img_tag = preg_replace( '/loading=["\']eager["\']/', 'loading="lazy"', $img_tag );
          } else {
            $img_tag = str_replace( '<img', '<img loading="lazy"', $img_tag );
          }
          
          return $img_tag;
        },
        $content
      );
    }
    return $content;
  }

  /**
   * Remove mistakenly preloaded assets
   *
   * @param [type] $content
   * @return void
   */
  public function force_remove_preloading_mistakes( $content ) {
    $patterns = apply_filters( 'diva_generator_preload_lists_removal', array() );
    if( !count( $patterns ) ) { return $content;}
    $pattern = '#<link[^>]+rel=["\']preload["\'][^>]+href=["\'][^"\']*(' . implode('|', $patterns) . ')[^"\']*["\'][^>]*>#i';
    $content = preg_replace_callback($pattern, function ($matches) {
        return '';
    }, $content);

    return $content;
  }

  private function remote_css_inline( $assetLink ) {
    $cssApi = wp_remote_get( $assetLink );
    if( is_wp_error( $cssApi ) ) { return ""; }
    $cssBody = wp_remote_retrieve_body( $cssApi );
    return $cssBody;
  }

  /**
   * Inline css for optimization
   *
   * @param [type] $tag
   * @return void
   */
  public function inline_css( $tag ) {
    preg_match("/href=['\"]([^'\"]+)['\"]/", $tag, $matches);
    if (empty($matches[1])) { return $tag; }

    $handle = 'adios-opt-css-' . uniqid();
    preg_match("/id=['\"]([^'\"]+)['\"]/", $tag, $handlmatch );
    if (!empty($matches[1])) { $handle = $handlmatch[1]; }
    
    $assetLink = $matches[1];
    $cssBody = "";
    if( stripos( $assetLink, home_url( '/' ) ) !== false || stripos( $assetLink, home_url( '/', 'http' ) ) !== false ) {
        // Remove all query parameters from $assetLink to make it a plain URL
        $plainassetLink = preg_replace('/\?.*/', '', $assetLink);
        $assetPath = str_replace( home_url('/'), ABSPATH, $plainassetLink );
        $assetPath = str_replace( home_url('/', 'http'), ABSPATH, $assetPath ); // in-case unsecure asset

        if( file_exists( $assetPath )) {
          $cssBody = file_get_contents( $assetPath );
        } else {
          $cssBody = $this->remote_css_inline( $assetLink );
        }
    } else {
        $cssBody = $this->remote_css_inline( $assetLink );
    }
    
    $cssBody = trim( preg_replace( '/\s+/', ' ', $cssBody ) );
    return "<style id=\"{$handle}\" type=\"text/css\" data-opt-adios-css-inline>{$cssBody}</style>";
  }

  public function preload_css( $tag ) {
    preg_match("/href=['\"]([^'\"]+)['\"]/", $tag, $matches);
    if (empty($matches[1])) { return $tag; }

    $handle = 'adios-opt-css-' . uniqid();
    preg_match("/id=['\"]([^'\"]+)['\"]/", $tag, $handlmatch );
    if (!empty($matches[1])) { $handle = $handlmatch[1]; }

    $assetLink = $matches[1];
    return '<link href="' . esc_url($assetLink) . '"  rel="preload" id="' . $handle . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\';" data-opt-css-deferred />
    <noscript><link href="' . esc_url($assetLink) . '"  rel="stylesheet" id="' . $handle . '" /></noscript>';
  }

  /**
   * Move to footer for defered css
   * Inline styles that in the above the fold content.
   *
   * @param [type] $content
   * @return void
   */
  public function force_opt_style_loader( $content ) {
    $styles_to_move = apply_filters( 'diva_generator_critical_css_lists', array(
      "et-cache",
    ));
    if( !count( $styles_to_move ) ) { return $content; }
    $combined_pattern = implode('|', $styles_to_move);
    $all_styles_pattern = '#<link\b(?=[^>]*rel=["\']stylesheet["\'])(?=[^>]*href=["\']([^"\']+)["\'])[^>]*>#i';

    preg_match_all($all_styles_pattern, $content, $all_matches);

    $matches_in_list = [];
    $matches_not_in_list = [];
    foreach ($all_matches[0] as $index => $tag) {
        $href = $all_matches[1][$index];
        if (preg_match('#' . $combined_pattern . '#i', $href)) {
          $matches_in_list[] = $tag; // matched styles (in $styles_to_move)
        } else {
          $matches_not_in_list[] = $tag; // unmatched styles (not in $styles_to_move)
        }
    }

    // force inline styles that are in the list
    foreach ($matches_in_list as $style_tag) {
        $content = str_replace($style_tag, $this->inline_css($style_tag), $content);
    }

    // remove all unmatched styles from the content
    $deferedStyles = "";
    foreach ($matches_not_in_list as $style_tag) {
        $content = str_replace($style_tag, "", $content);
        $deferedStyles .= $this->preload_css( $style_tag ) . "\n";
    }
    $content = str_replace('</body>', $deferedStyles . "\n</body>", $content);
    return $content;
  }

  /**
   * Force move to footer defered css
   *
   * @param [type] $content
   * @return void
   */
  // public function force_move_defered_css_footer( $content ) {
  //   $pattern = '#<link\b(?=[^>]*rel=["\']preload["\'])(?=[^>]*as=["\']style["\'])(?=[^>]*onload=["\']this\.onload=null;this\.rel=\'stylesheet\';["\'])[^>]*>#i';
  //   preg_match_all($pattern, $content, $matches);
    
  //   $deferedStyles = "";
  //   if (!empty($matches[0])) {
  //     foreach ($matches[0] as $tag) {
  //       $content = str_replace($tag, "", $content);
  //       $deferedStyles .= $this->preload_css( $tag ) . "\n";
  //     }
  //   }
  //   $content = str_replace('</body>', $deferedStyles . "\n</body>", $content);
  //   return $content;
  // }


  public function google_fonts_optimization( $content ) {
    // process only the inlined google font, otherwise move font to footer which is not prio.
    // Find all <style> tags in the content
    preg_match_all('#<style[^>]*>(.*?)</style>#is', $content, $style_matches);

    if (!empty($style_matches[0])) {
      foreach ($style_matches[0] as $style_index => $style_tag) {

        // Find all Google Fonts URLs in the style content
        preg_match_all('/url\((["\']?)(https:\/\/fonts\.gstatic\.com\/[^)\'"]+)\1\)/i', $style_tag, $font_urls);

        if (!empty($font_urls[2])) {

          $upload_dir = wp_upload_dir();
          $fonts_dir = WP_CONTENT_DIR . '/google-fonts/';
          $fonts_url = content_url('google-fonts/');
          if (!file_exists($fonts_dir)) {
            wp_mkdir_p($fonts_dir);
          }

          $new_tag = $style_tag;

          foreach ($font_urls[2] as $font_url) {

            $font_filename = basename(parse_url($font_url, PHP_URL_PATH));
            $local_font_path = $fonts_dir . $font_filename;
            $local_font_url = trailingslashit($fonts_url) . $font_filename;

            if (file_exists($local_font_path)) {
              $new_tag = str_replace($font_url, $local_font_url, $new_tag);
              continue;
            }
            
            $font_response = wp_remote_get($font_url);
            if (is_wp_error($font_response)) {
              continue;
            }
            $font_data = wp_remote_retrieve_body($font_response);
            error_log( $font_data );
            // Save the font file if it doesn't exist
            file_put_contents($local_font_path, $font_data);
            $new_tag = str_replace($font_url, $local_font_url, $new_tag);
          }

          $content = str_replace( $style_tag, $new_tag, $content );
        }

      }
    }

    return $content;
  }


  public function lazyload_iframes_with_placeholders( $content ) {

    // Define exclusions (iframe srcs that should NOT be lazyloaded)
    $exclusions = apply_filters('adiosgenerator_lazyload_iframe_exclusions', array(
      'exclude-lazy-placeholder'
    ));

    // Find all iframes with a src attribute
    if (preg_match_all('/<iframe\b([^>]*)\bsrc=(["\'])(.*?)\2([^>]*)>/is', $content, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $iframe_match) {
        $full_tag = $iframe_match[0];

        // Only replace if not already lazyloaded (i.e., doesn't have data-src)
        if (preg_match('/\bsrc=(["\'])(.*?)\1/i', $full_tag, $src_match)) {
          $src_value = $src_match[2];

          $excluded = false;
          foreach ($exclusions as $exclusion) {
            if (stripos($src_value, $exclusion) !== false) {
              $excluded = true;
              break;
            }
          }
          if ($excluded) {
            continue;
          }

          $new_tag = preg_replace('/\bsrc=(["\'])(.*?)\1/i', '', $full_tag, 1);
          $placeholder = "about:blank";
          $new_tag = preg_replace('/<iframe\b/i', '<iframe data-src="' . $src_value . '" src="'. $placeholder .'" ', $new_tag, 1);
          $content = str_replace($full_tag, $new_tag . "<noscript>".$full_tag."</noscript>", $content);
        }
      }
    }

    return $content;
  }

  public function lazyload_img_with_placeholders( $content ) {
    
    // Find all <img> tags with loading="lazy" attribute
    if (preg_match_all('/<img\b([^>]*)\bloading=(["\'])lazy\2([^>]*)>/is', $content, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $img_match) {
        $full_tag = $img_match[0];

        // Find the src attribute in the tag
        if (preg_match('/\bsrc=(["\'])(.*?)\1/i', $full_tag, $src_match)) {
          $src_value = $src_match[2];

          // Only replace if not already has data-src
          $new_tag = preg_replace('/\bsrc=(["\'])(.*?)\1/i', '', $full_tag, 1);
          $placeholder = "data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%2050%2050'%3E%3C/svg%3E";
          $new_tag = preg_replace('/<img\b/i', '<img data-src="' . $src_value . '" src="'. $placeholder .'" ', $new_tag, 1);
          $content = str_replace($full_tag, $new_tag . "<noscript>".$full_tag."</noscript>", $content);
        }
      }
    }

    return $content;
  }

}