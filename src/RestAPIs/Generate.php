<?php

namespace WebGenerator\RestAPIs;

use WebGenerator\GeneratorREST;
use WebGenerator\GeneratorUtilities;
use ET_Core_PageResource;

class Generate extends GeneratorREST {

  private array $generate_classes;

  public function __construct() {
    $this->generate_classes = array(
      \WebGenerator\Generate\Logos::class,
      \WebGenerator\Generate\ProcessContent::class,
      \WebGenerator\Generate\ServicesPages::class,
      \WebGenerator\Generate\StockPhotos::class,
      \WebGenerator\Generate\AIContents::class,
      \WebGenerator\Generate\SEOPages::class,
      \WebGenerator\Generate\Finalize::class
    );
  }

  public function getScheduleGroup() {
    return "adiosgenerator";
  }

  public function setEvent( $event ) {
    $prefix = "adiosgenerator_generate_";
    return $prefix . $event;
  }

  public function taskId() {
    return "wp_adiosgenerator_generate_" . wp_generate_uuid4();
  }

  public function route(): void {
    add_action( "rest_api_init", function() {
      register_rest_route( 'adiosgenerator', 'client/generate', array(
        'methods' => 'POST',
        'callback' => array( $this, "load" ),
        'permission_callback' => array( $this, 'authorize' )
      ));
    });

    add_action( "rest_api_init", function() {
      register_rest_route( 'adiosgenerator', 'client/generate/status', array(
        'methods' => 'GET',
        'callback' => array( $this, "status" ),
        'permission_callback' => array( $this, 'authorize' )
      ));
    });

    // load initialize action for schedule
    self::autoload_init($this->generate_classes);
  }

  public function load(): \WP_REST_Response {
    // load all action schedules
    $taskIds = self::autoload_schedule($this->generate_classes);

    // reset statuses when executing the generate api
    $statuses = self::getEventStatus($this->generate_classes);
    foreach( $statuses as $status ) {
      update_option( $status['event'], 0 );
    }

    $this->stale_schedule();
    return wp_send_json_success( array(
      "message" => "Generate is Running...",
      "tasks" => $taskIds
    ));
  }

  // autoload init method from class that has subclass for this class  
  public static function autoload_init(array $classList): void {
    foreach ($classList as $class) {
      if (class_exists($class) && is_subclass_of($class, 'WebGenerator\RestAPIs\Generate')) {
        if (method_exists($class, 'init')) {
          (new $class)->init();
        }
      }
    }
  }

  // autoload schedule method from class that has subclass for this class
  public static function autoload_schedule(array $classList): array {
    $taskIds = array();
    foreach ($classList as $class) {
      if (class_exists($class) && is_subclass_of($class, 'WebGenerator\RestAPIs\Generate')) {
        if (method_exists($class, 'schedule')) {
          $taskIds[] = (new $class)->schedule();
        }
      }
    }
    return $taskIds;
  }


  // get events
  public static function getEventStatus(array $classList): array {
    $events = array();
    foreach ($classList as $class) {
      if (class_exists($class) && is_subclass_of($class, 'WebGenerator\RestAPIs\Generate')) {
        if (method_exists($class, 'getEvent')) {
          $event = (new $class)->getEvent();
          $events[] = array(
            'event' => $event,
            'status' => get_option( $event, 0 )
          );
        }
      }
    }
    return $events;
  }

  /**
   * Posts to be handled upon replacements
   *
   * @return array<\WP_Post>
   */
  public function get_posts_content_generate(): array {
    $posts = get_posts(array(
      'posts_per_page' => -1,
      'post_type' => array(
        "page",
        "post",
        "project",
        "diva_services",
        "et_body_layout",
        "et_footer_layout",
        "et_header_layout",
        "et_template",
        "et_theme_builder"
      )
    ));
    return $posts;
  }

  // method for stale schedule for immediate purpose
  public function stale_schedule() {
    if (function_exists('spawn_cron')) {
      spawn_cron(); // wakes Action Scheduler immediately
    } else {
      wp_remote_post(site_url('wp-cron.php'), [
        'timeout'  => 0.01,
        'blocking' => false,
      ]);
    }
  }

  public function status() {
    $this->stale_schedule();
    $statuses = self::getEventStatus($this->generate_classes);
    return wp_send_json_success( array(
      "message" => "Event Statuses",
      "statuses" => $statuses
    ));
  }
}
