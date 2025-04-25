<?php

use \Divi\Onboarding\Helpers;

function adiosgenerator_pages_menu( WP_REST_Request $request ) {
  
  if ( isset( $_POST['page_titles'] ) ) {
    $page_titles = array_map( 'sanitize_text_field', $_POST['page_titles'] );
		$result      = Helpers\create_menu_with_pages( $page_titles );
  
    wp_send_json_success( $result );
  }

  wp_send_json_error();
}