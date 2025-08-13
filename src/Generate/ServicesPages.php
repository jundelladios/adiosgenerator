<?php

namespace WebGenerator\Generate;

use WebGenerator\RestAPIs\Generate;

use WebGenerator\GeneratorUtilities;
use WebGenerator\GeneratorProcessContent;

class ServicesPages extends Generate {

  private $event;

  public function __construct() {
    $this->event = $this->setEvent( "services_pages" );
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
    $client = $apidata->client;

    // remove all published dummy services
    $publishedServices = get_posts( array(
      'post_type' => 'diva_services',
      'post_status' => 'publish',
      'numberposts' => -1
    ));
    foreach( $publishedServices as $service ) {
      wp_delete_post( $service->ID, true );
    }
    
    $firstDraftSpage = get_posts( array(
      'post_type' => 'diva_services',
      'numberposts' => 1,
      'post_status' => 'draft'
    ));

    $template = null;
    if( !empty( $firstDraftSpage ) ) {
      $template = $firstDraftSpage[0];
    }

    if( !$template ) { return; }
    
    $services = explode( ',', $client->services ?? '' );
    foreach( $services as $service ) {
      $service = trim( $service );
      $postExists = get_posts( array(
        'post_type' => 'diva_services',
        'title' => $service,
        'numberposts' => 1,
        'post_status'    => 'any',
      ));
      if( empty( $postExists)) {
        $post_id = GeneratorUtilities::duplicate_post( $template->ID, $service, "publish" );
        $post = get_post( $post_id );
        $content = $post->post_content;
        $content = str_replace(
          $template->post_title,
          $post->post_title,
          $content
        );
        wp_update_post([
          'ID' => $post_id,
          'post_content' => $content
        ]);
      }
    }
  }
}