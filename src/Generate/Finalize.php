<?php

namespace WebGenerator\Generate;

use WebGenerator\RestAPIs\Generate;

use WebGenerator\GeneratorAPI;
use WebGenerator\GeneratorCache;

class Finalize extends Generate {

  private $event;

  public function __construct() {
    $this->event = $this->setEvent( "finalize" );
  }
  
  public function getEvent() {
    return $this->event;
  }

  /**
    * generate page and post contents from AI
    * @return void
    */
  public function execute() {
    // remove all previous comments
    $all_comments = get_comments(['status' => 'all', 'number' => 0]);
    foreach ($all_comments as $comment) {
      wp_delete_comment($comment->comment_ID, true);
    }

    // clear cache
    wp_cache_flush();

    // clear cache
    GeneratorCache::clear_cache();

    // execute app generated finalize status
    GeneratorAPI::run(
      GeneratorAPI::generatorapi( "/api/trpc/appTokens.finalize" ),
      array()
    );

    update_option( $this->getEvent(), 1 );
  }
}