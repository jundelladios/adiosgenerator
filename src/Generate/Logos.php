<?php

namespace WebGenerator\Generate;

use WebGenerator\RestAPIs\Generate;

use WebGenerator\GeneratorUtilities;
use ET_Core_PageResource;

class Logos extends Generate {

  private $event;

  public function __construct() {
    $this->event = $this->setEvent( "logos" );
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
    $logo = $this->logo();
    $secondary = $this->secondary_logo();
    $favicon = $this->favicon();
    update_option( $this->getEvent(), 1 );
  }

  private function logo() {
    $apidata = $this->get_client();
    $retdata = $apidata->client;
    $divi = (array) $apidata->divi;

    $thelogo = $retdata->logo;
    $logo = GeneratorUtilities::upload_file_by_url(
      $thelogo,
      sanitize_title( $retdata->site_name . "-logo" ),
      sanitize_title( $retdata->site_name . "-logo" )
    );
    
    if( $logo ) {
      update_option( GeneratorUtilities::et_adiosgenerator_option("logo"), $logo );
      if( function_exists( 'et_update_option') ) {
        et_update_option( "divi_logo", wp_get_attachment_url( $logo ) );
      }

      $posts = $this->get_posts_content_generate();
      foreach( $posts as $pst ) {
        $content = $pst->post_content;
        $content = preg_replace('#https?://[^\s\'"]*/site-logo\.[a-zA-Z0-9]+#', wp_get_attachment_url( $logo ), $content);
        wp_update_post([
          'ID' => $pst->ID,
          'post_content' => $content
        ]);
        ET_Core_PageResource::do_remove_static_resources( $pst->ID, 'all' );
      }
      return "Logo has been set, attachment ID:" . $logo;
    } else {
      return "Failed to set logo";
    }
  }

  private function favicon() {
    $apidata = $this->get_client();
    $retdata = $apidata->client;
    $divi = (array) $apidata->divi;

    $thefavicon = $retdata->favicon;
    $favicon = GeneratorUtilities::upload_file_by_url(
      $thefavicon,
      sanitize_title( $retdata->site_name . "-favicon" ),
      sanitize_title( $retdata->site_name . "-favicon" )
    );
    
    if( $favicon ) {
      update_option( 'site_icon', $favicon );
      return 'Logo has been set, attachment ID: ' . $favicon;
    } else {
      return 'Failed to set favicon';
    }
  }

  private function secondary_logo() {
    $apidata = $this->get_client();
    $retdata = $apidata->client;
    $divi = (array) $apidata->divi;

    $thelogo = $retdata->logo;
    $logo = GeneratorUtilities::upload_file_by_url(
      $thelogo,
      sanitize_title( $retdata->site_name . "-logo-alternative" ),
      sanitize_title( $retdata->site_name . "-logo-alternative" )
    );
    
    if( $logo ) {
      update_option( GeneratorUtilities::et_adiosgenerator_option("logo_2"), $logo );
      $posts = $this->get_posts_content_generate();

      foreach( $posts as $pst ) {
        $content = $pst->post_content;
        $content = preg_replace('#https?://[^\s\'"]*/site-logo-secondary\.[a-zA-Z0-9]+#', wp_get_attachment_url( $logo ), $content);
        wp_update_post([
          'ID' => $pst->ID,
          'post_content' => $content
        ]);
        ET_Core_PageResource::do_remove_static_resources( $pst->ID, 'all' );
      }

      return 'Logo has been set, attachment ID: ' . $logo;
    } else {
      return 'Failed to set alternative logo';
    }
  }
}