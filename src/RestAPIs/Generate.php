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

  public function getPexelsFileName( $url ) {
    $pexelsUrlPath = parse_url($url, PHP_URL_PATH);
    $pexelsUrlPathSegments = explode('/', trim($pexelsUrlPath, '/'));
    $lastSegment = end($pexelsUrlPathSegments);
    return $lastSegment;
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
    add_action( 'adiosgenerator_upload_stock_photo_replace', array( $this, 'upload_stock_photo_replace' ));
    add_action( 'adiosgenerator_upload_stock_video_replace', array( $this, 'upload_stock_video_replace' ));
  }

  /**
   * Handles replacing a stock photo in a post's content and optionally setting it as the featured image.
   *
   * @param array $args {
   *   @type string  $post_id           The ID of the post to update.
   *   @type string  $stock_photo       The URL of the new stock photo.
   *   @type string  $last_photo        The URL of the photo to be replaced.
   *   @type string  $alt               The alt text for the new photo.
   *   @type string  $filename          The filename for the new photo.
   *   @type bool    $is_featured_image Whether to set as featured image.
   * }
   */
  public function upload_stock_photo_replace( $args ) {
    if (
      !is_array($args) ||
      empty($args['post_id']) ||
      empty($args['stock_photo']) ||
      empty($args['last_photo'])
    ) {
      return;
    }

    $post_id = $args['post_id'];
    $stock_photo = $args['stock_photo'];
    $last_photo = $args['last_photo'];
    $alt = isset($args['alt']) ? $args['alt'] : '';
    $filename = isset($args['filename']) ? $args['filename'] : '';
    $is_featured_image = !empty($args['is_featured_image']);

    // Download and upload the new stock photo to the media library
    $attachment_id = GeneratorUtilities::upload_file_by_url(
      $stock_photo,
      $alt,
      $filename
    );

    if (!$attachment_id) {
      // If upload failed, just replace the URL in content as fallback
      $post = get_post($post_id);
      if ($post) {
        $content = str_replace($last_photo, $stock_photo, $post->post_content);
        wp_update_post([
          'ID' => $post_id,
          'post_content' => $content
        ]);
      }

      if (class_exists('ET_Core_PageResource')) {
        \ET_Core_PageResource::do_remove_static_resources($post_id, 'all');
      }

      return;
    }

    // Replace the old photo URL with the new attachment URL in post content
    $new_url = wp_get_attachment_url($attachment_id);
    $post = get_post($post_id);
    if ($post && $new_url) {
      $content = str_replace($last_photo, $new_url, $post->post_content);
      wp_update_post([
        'ID' => $post_id,
        'post_content' => $content
      ]);
    }

    // Set as featured image if requested
    if ($is_featured_image && $attachment_id) {
      set_post_thumbnail($post_id, $attachment_id);
    }

    // Optionally update alt text
    if ($alt && $attachment_id) {
      update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt);
    }

    // Remove static resources cache for this post
    if (class_exists('ET_Core_PageResource')) {
      \ET_Core_PageResource::do_remove_static_resources($post_id, 'all');
    }

    return true;
  }


  /**
   * Handles replacing stock videos and upload with the given args.
   *
   * @param array $args {
   *   @type int    $post_id     The post ID.
   *   @type string $stock_video The new stock video URL.
   *   @type string $last_video  The old video URL to be replaced.
   *   @type string $filename    The filename for the new video.
   * }
   */
  public function upload_stock_video_replace( $args ) {
    $post_id     = $args['post_id']     ?? 0;
    $stock_video = $args['stock_video'] ?? '';
    $last_video  = $args['last_video']  ?? '';
    $filename    = $args['filename']    ?? '';

    if ( ! $post_id || ! $stock_video || ! $last_video ) {
      return;
    }

    // Try to upload the video from the stock_video URL
    $attachment_id = null;
    $attachment_id = GeneratorUtilities::upload_file_by_url(
      $stock_video,
      null,
      $filename
    );

    // If upload failed, just replace the URL in content as fallback
    if ( ! $attachment_id ) {
      $post = get_post( $post_id );
      if ( $post ) {
        $content = str_replace( $last_video, $stock_video, $post->post_content );
        wp_update_post( [
          'ID' => $post_id,
          'post_content' => $content
        ] );
      }

      if ( class_exists( 'ET_Core_PageResource' ) ) {
        \ET_Core_PageResource::do_remove_static_resources( $post_id, 'all' );
      }

      return;
    }

    // Replace the old video URL with the new attachment URL in post content
    $new_url = wp_get_attachment_url( $attachment_id );
    $post = get_post( $post_id );
    if ( $post && $new_url ) {
      $content = str_replace( $last_video, $new_url, $post->post_content );
      wp_update_post( [
        'ID' => $post_id,
        'post_content' => $content
      ] );
    }

    // Remove static resources cache for this post
    if ( class_exists( 'ET_Core_PageResource' ) ) {
      \ET_Core_PageResource::do_remove_static_resources( $post_id, 'all' );
    }

    return true;
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
