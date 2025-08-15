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

      register_rest_route( 'adiosgenerator', 'client/generate/status', array(
        'methods' => 'GET',
        'callback' => array( $this, "status" ),
        'permission_callback' => '__return_true'
      ));
    });

    add_action( 'adiosgenerator_generate_execute', array( $this, 'executeAll' ));
  }

  public function load(): \WP_REST_Response {
    $this->get_client();

    // execute long task generation
    as_enqueue_async_action( 
      "adiosgenerator_generate_execute", 
      [ 'task_id' => $this->taskId() ], 
      $this->getScheduleGroup(),
      true
    );

    // reset statuses when executing the generate api
    $statuses = self::getEventStatus($this->generate_classes);
    foreach( $statuses as $status ) {
      update_option( $status['event'], 0 );
    }
    
    $this->stale_schedule();
    return wp_send_json_success( array(
      "message" => "Generate is Running..."
    ));
  }


  public function executeAll() {
    // execute all generate classes, combine all process to avoid queueing schedule
    self::autoload_execute($this->generate_classes);
  }

  // autoload execute method from class that has subclass for this class
  public static function autoload_execute(array $classList): void {

    
    foreach ($classList as $class) {
      if (class_exists($class) && is_subclass_of($class, 'WebGenerator\RestAPIs\Generate')) {
        if (method_exists($class, 'execute')) {
          try {
            (new $class)->execute();
          } catch(\Exception $e) {}
        }
      }
    }
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
    $allowed_origins = array(
      constant('ADIOSGENERATOR_API_URL')
    );
    if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
      header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    }
    header('Access-Control-Allow-Methods: GET');
    header('Access-Control-Allow-Headers: Content-Type');

    $this->stale_schedule();
    $statuses = self::getEventStatus($this->generate_classes);
    return wp_send_json_success( array(
      "message" => "Event Statuses",
      "statuses" => $statuses
    ));
  }
}
