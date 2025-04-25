<?php

function adiosgenerator_epanel_save( WP_REST_Request $request ) {
  $_POST = (array) json_decode($request->get_body(), true);
  if(!defined( 'ET_BUILDER_DIR' )) {
    return array(
      'result' => 0
    );
  }

  adiosgenerator_epanel_save_data('js_disabled');
  if( function_exists( 'et_cache_clear' ) ) {
    et_cache_clear();
  }
  return array(
    'result' => 1
  );
}


function adiosgenerator_et_theme_builder_api_reset() {
  if(function_exists( 'et_theme_builder_api_reset' )) {
    $live_id = et_theme_builder_get_theme_builder_post_id( true, false );
    if ( $live_id > 0 && current_user_can( 'delete_others_posts' ) ) {
      wp_trash_post( $live_id );
      // Reset cache when theme builder is reset.
      ET_Core_PageResource::remove_static_resources( 'all', 'all', true );
    }
    et_theme_builder_trash_draft_and_unused_posts();
    wp_send_json_success();
  }
  wp_send_json_error();
}

function adiosgenerator_signin() {
  $redirectURL = isset($_GET['redirect']) ? $_GET['redirect'] : "index.php";
  if(!isset($_GET['token'])) {
    wp_redirect(admin_url($redirectURL), 302);
  }
  
  $payload = array(
    'token' => urldecode($_GET['token'])
  );

  $args = array(
    'headers' => array(
      'Content-Type' => 'application/json',
      'Accept' => 'application/json'
    ),
    'body' => json_encode(array(
      "json" => $payload
    ))
  );

  $request = wp_remote_post( adiosgenerator_api_url() . "/api/trpc/sites.wpSignin", $args );
  $body = wp_remote_retrieve_body( $request );
  $response = json_decode($body);
  if ( !is_wp_error( $request ) && !isset($body->error) ) {
    $userId = $response->result->data->json->user_id;
    $userLogin = $response->result->data->json->user_login;
    $appPassword = $response->result->data->json->app_password;
    if(isset($userId) && isset($userLogin)) {
      $userdata = get_user_by( "ID", $userId );
      $auth = wp_authenticate_application_password( $userdata, $userdata->userLogin, $appPassword);
      if(!is_wp_error( $auth )) {
        wp_set_current_user(
          (int) $userId, 
          $userLogin
        );
        wp_set_auth_cookie(
          (int) $userId
        );
      }
    }
  }
  wp_redirect(admin_url($redirectURL), 302);
  ?>
  <html>
    <head>
      <meta http-equiv="refresh" content="0;URL=<?php echo admin_url($redirectURL); ?>">
    </head>
    <body>
      <?php _e('Redirection in progress...', 'adiosgenerator');?>☂
      <script>
        document.addEventListener("DOMContentLoaded", function(event) {
          window.location = "<?php echo admin_url($redirectURL); ?>";
        });
      </script>
    </body>
  </html>
  <?php
  exit;
}

function adiosgenerator_url_to_attachment( WP_REST_Request $request ) {

  $_POST = (array) json_decode($request->get_body(), true);
  $alt = isset( $_POST['alt'] ) ? $_POST['alt'] : null;
  if(!isset( $_POST['image_url'] )) {
    return null;
  }

  return array(
    "attachment_id" => adiosgenerator_upload_file_by_url( $_POST['image_url'], $alt )
  );
}