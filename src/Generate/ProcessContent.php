<?php

namespace WebGenerator\Generate;

use WebGenerator\RestAPIs\Generate;

use WebGenerator\GeneratorUtilities;
use WebGenerator\GeneratorProcessContent;
use ET_Core_PageResource;
use ET_Builder_Module_Social_Media_Follow_Item;

class ProcessContent extends Generate {

  private $event;

  public function __construct() {
    $this->event = $this->setEvent( "process_content" );
  }

  public function init() {
    add_action( $this->getEvent(), array( $this, 'execute' ));
  }

  public function schedule() {
    as_enqueue_async_action( $this->getEvent(), [ 'task_id' => $this->taskId() ], $this->getScheduleGroup());
    return $this->taskId();
  }

  public function getEvent() {
    return $this->event;
  }

  public function execute() {
    $this->runExecute();
    update_option( $this->getEvent(), 1 );
  }

  public function runExecute() {
    $apidata = $this->get_client();
    $retdata = $apidata->client;
    $placeholder = $apidata->placeholder;
    $divi = (array) $apidata->divi;

    $posts = $this->get_posts_content_generate();

    $prevColors = get_option( GeneratorUtilities::et_adiosgenerator_option( "colors" ), array(
      'accent_color' => et_get_option( 'accent_color', $divi["accent_color"] ),
      'secondary_accent_color' => et_get_option( 'secondary_accent_color', $divi["secondary_accent_color"] )
    ));

    $logo = get_option( GeneratorUtilities::et_adiosgenerator_option( "logo" ) );
    $logo2 = get_option( GeneratorUtilities::et_adiosgenerator_option( "logo_2" ) );
    $smFields = (new ET_Builder_Module_Social_Media_Follow_Item)->get_fields();

    $postIds = array();
    foreach( $posts as $pst ) {
      $content = $pst->post_content;

      if( !empty( trim( $content ) )) {
        $postIds[] = $pst->ID;
      }

      // accents replace
      $content = preg_replace('/' . preg_quote($prevColors["accent_color"], '/') . '/i', $divi["accent_color"], $content);
      $content = preg_replace('/' . preg_quote($prevColors["secondary_accent_color"], '/') . '/i', $divi["secondary_accent_color"], $content);

      // logos replace
      $content = preg_replace('#https?://[^\s\'"]*/site-logo\.[a-zA-Z0-9]+#', wp_get_attachment_url( $logo ), $content);
      $content = preg_replace('#https?://[^\s\'"]*/site-logo-secondary\.[a-zA-Z0-9]+#', wp_get_attachment_url( $logo2 ), $content);

      // contact number replace
      $content = str_replace($placeholder->contact_number, $retdata->contact_number, $content);
      $content = str_replace(
        preg_replace('/\D+/', '', $placeholder->contact_number),
        preg_replace('/\D+/', '', $retdata->contact_number),
        $content
      );

      // email replace
      $content = str_replace($placeholder->email_address, $retdata->email_address, $content);

      // maps and address replace
      $content = str_replace( $placeholder->site_address, $retdata->site_address, $content);
      $content = (new GeneratorProcessContent)->replace_google_maps_iframe_address( $content, $retdata->site_address );
      $content = str_replace( 
        str_replace( " ", "+", $placeholder->site_address ), 
        str_replace( " ", "+", $retdata->site_address ),
        $content
      );

      // social media replace
      $content = preg_replace_callback(
      '/\[et_pb_social_media_follow([^\]]*)\](.*?)\[\/et_pb_social_media_follow\]/s',
        function ($matches) use($retdata, $smFields) {
            $attributes = $matches[1]; // Keeps the original attributes
            $socMediaContent = "";
            foreach( $retdata->social_media as $socmed ) {
              if( isset( $smFields['social_network']['value_overwrite'][$socmed->social] ) ) {
                $socMediaColor = $smFields['social_network']['value_overwrite'][$socmed->social];
                $socMediaContent .= "[et_pb_social_media_follow_network {$attributes} social_network=\"{$socmed->social}\" url=\"{$socmed->link}\" background_color=\"{$socMediaColor}\"]{$socmed->social}[/et_pb_social_media_follow_network]";
              }
            }
            return "[et_pb_social_media_follow{$attributes}]{$socMediaContent}[/et_pb_social_media_follow]";
        },
        $content
      );

      // insights replace
      $content = str_replace( $placeholder->about_content, $retdata->insights, $content );

      // socials replace
      $socialPages = array();
      foreach( $retdata->social_media as $socmed ) {
        $socialPages[$socmed->social] = $socmed->link;
        if( $socmed->social === "twitter" ) {
          $socialPages["x"] = $socmed->link;
        }
      }

      $social_platforms = implode('|', array_keys($socialPages));
      $social_pattern = '/https:\/\/[a-z0-9\-\.]*(' . $social_platforms . ')[a-z0-9\-\.]*\.com\/' . preg_quote( $placeholder->social_slug, '/' ) . '/i';
      
      $content = preg_replace_callback($social_pattern, function ($matches) use ($socialPages) {
        return $socialPages[$matches[1]] ?? $matches[0]; // Fallback if not found
      }, $content);

      // site name and slogan
      $content = str_replace( $placeholder->site_name, $retdata->site_name, $content );
      $content = str_replace( $placeholder->tagline, $retdata->slogan, $content );
      $content = $this->dynamic_footer_sitename_replace( $content, $placeholder->site_name, $retdata->site_name );

      wp_update_post([
        'ID' => $pst->ID,
        'post_content' => $content
      ]);

      ET_Core_PageResource::do_remove_static_resources( $pst->ID, 'all' );
    }

    // homepage SEO
    $front_page_id = get_option( 'page_on_front' );
    if( $front_page_id ) {
      update_post_meta( $front_page_id, '_wds_title', $retdata->meta_title );
      update_post_meta( $front_page_id, '_wds_metadesc', $retdata->meta_description );
      update_post_meta( $front_page_id, '_wds_focus-keywords', $retdata->meta_keyword );
    }

    // site title and tagline
    update_option('blogname', $retdata->site_name);
    update_option('blogdescription', $retdata->slogan);

    // gravity forms email
    $this->gform_notification_replace_default_email( $retdata->email_address );
  }


  /**
   * Undocumented function
   *
   * @param [type] $content
   * @param string $placeholder
   * @param string $sitename
   * @return void
   */
  public function dynamic_footer_sitename_replace( $content, $placeholder="", $sitename="" ) {
    $content = wp_unslash( $content );
    $dynamic_contents = et_builder_get_dynamic_contents($content);
    foreach ( $dynamic_contents as $dynamic_item ) {
      $dynamic_item_parsed = et_builder_parse_dynamic_content( $dynamic_item );
      $after_content = $dynamic_item_parsed->get_settings( 'after' );
      if ( $after_content !== '' ) {
        $dynamic_item_parsed->set_settings( 'after', str_replace( $placeholder, $sitename, $after_content ?? "" ) );
        $re_serialized_dynamic_item = $dynamic_item_parsed->serialize();
        $content = str_replace( $dynamic_item, $re_serialized_dynamic_item, $content);
      }
    }
    return wp_slash( $content );
  }


  /**
   * Undocumented function
   *
   * @param [type] $admin_email
   * @return void
   */
  public function gform_notification_replace_default_email( $admin_email ) {
    global $wpdb;

    // disregard if there's no gravity form
    if ( !class_exists( 'GFCommon' ) ) { return false; }

    $columns = array( 'display_meta', 'notifications' );
    foreach( $columns as $col ) {
      $wpdb->query(
        $wpdb->prepare(
          "UPDATE {$wpdb->prefix}gf_form_meta SET {$col} = REPLACE({$col}, %s, %s) WHERE {$col} LIKE %s",
          '{admin_email}',
          $admin_email,
          '%{admin_email}%'
        )
      );
    }

    // delete all gform entries
    $entry_columns = array( 'entry', 'entry_meta', 'entry_notes' );
    foreach( $entry_columns as $col ) {
      $wpdb->query(
        "DELETE FROM {$wpdb->prefix}gf_{$col}"
      );
    }
  }
}