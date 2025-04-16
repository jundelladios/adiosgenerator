<?php

/**
 * Plugin Name: AD-IOS Web Generator
 * Plugin URI: https://github.com/jundelladios?tab=repositories
 * Description: Web Generator from AD-IOS Team
 * Version: 1.0.0
 * Author: AD-IOS Web Development
 * Author URI: https://ad-ios.com/website-support-ticket/
 * License: GPL-2.0+
 * Text Domain: adiosgenerator
 * Domain Path: /languages
 */

function adiosgenerator_api_url() {
  if( defined('WP_GENERATOR_HOME_URL') ) {
      return WP_GENERATOR_HOME_URL;
  }
  return "https://adios-webgenerator.com";
}

function adiosgenerator_get_attachment_by_post_name( $post_name ) {
	$args = array(
			'posts_per_page' => 1,
			'post_type'      => 'attachment',
			'name'           => trim( $post_name ),
	);

	$get_attachment = new WP_Query( $args );

	if ( ! $get_attachment || ! isset( $get_attachment->posts, $get_attachment->posts[0] ) ) {
			return false;
	}

	return $get_attachment->posts[0];
}


function adiosgenerator_upload_file_by_url( $image_url, $alt=null ) {
  // it allows us to use download_url() and wp_handle_sideload() functions
	require_once( ABSPATH . 'wp-admin/includes/file.php' );

	// prevent redownload if filename already exists or uploaded.
	$fileBaseName = basename( $image_url );
	$existingAttachment = adiosgenerator_get_attachment_by_post_name( $fileBaseName );
	if($existingAttachment) {
		return $existingAttachment->ID;
	}

	// download to temp dir
	$temp_file = download_url( $image_url );

	if( is_wp_error( $temp_file ) ) {
		return false;
	}

	// move the temp file into the uploads directory
	$file = array(
		'name'     => basename( $image_url ),
		'type'     => mime_content_type( $temp_file ),
		'tmp_name' => $temp_file,
		'size'     => filesize( $temp_file ),
	);

	$sideload = wp_handle_sideload(
		$file,
		array(
			'test_form'   => false // no needs to check 'action' parameter
		)
	);

	if( ! empty( $sideload[ 'error' ] ) ) {
		// you may return error message if you want
		return false;
	}

	// it is time to add our uploaded image into WordPress media library
	$attachment_id = wp_insert_attachment(
		array(
			'guid'           => $sideload[ 'url' ],
			'post_mime_type' => $sideload[ 'type' ],
			'post_title'     => basename( $sideload[ 'file' ] ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		),
		$sideload[ 'file' ]
	);

	if( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		return false;
	}

	// update medatata, regenerate image sizes
	require_once( ABSPATH . 'wp-admin/includes/image.php' );

	wp_update_attachment_metadata(
		$attachment_id,
		wp_generate_attachment_metadata( $attachment_id, $sideload[ 'file' ] )
	);

  if($alt) {
    update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
  }

	return $attachment_id;
}


function adiosgenerator_api_post_exec( $endpoint, $params=array(), $credentials = null ) {
	$apiParams = array(
		'headers' => array(
			'Content-Type' => 'application/json',
			'Accept' => 'application/json'
		),
		'body' => json_encode($params)
	);

	if(isset( $credentials['user'] ) && isset( $credentials['password'] )) {
		$apiParams['headers']['Authorization'] = 'Basic ' . base64_encode($credentials['user'] . ':' . $credentials['password']);
	}

	$request = wp_remote_post( $endpoint, $apiParams);
	if ( is_wp_error( $request ) ) {
		return false;
	}

	$body = wp_remote_retrieve_body( $request );
  $json = json_decode( $body );

	return $json;
}


define("ADIOSGENERATOR_PLUGIN_URI", plugin_dir_url( __FILE__ ));

define("ADIOSGENERATOR_PLUGIN_DIR", plugin_dir_path( __FILE__ ) );


require_once ADIOSGENERATOR_PLUGIN_DIR . 'divi.php';

require_once ADIOSGENERATOR_PLUGIN_DIR . 'rest.php';

require_once ADIOSGENERATOR_PLUGIN_DIR . 'rest-functions.php';

require_once ADIOSGENERATOR_PLUGIN_DIR . 'wpcli.php';