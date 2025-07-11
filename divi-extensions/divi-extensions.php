<?php

function diex_array_to_html_attributes( $attrs ) {
	$output = '';
	foreach ( $attrs as $key => $value ) {
		if( !empty( $value )) {
			$output .= sprintf( ' %s="%s" ', esc_attr( $key ), esc_attr( $value ) );
		}
	}
	return trim( $output );
}

if ( ! function_exists( 'diex_initialize_extension' ) ):
/**
 * Creates the extension's main class instance.
 *
 * @since 1.0.0
 */
function diex_initialize_extension() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/DiviExtensions.php';
}
add_action( 'divi_extensions_init', 'diex_initialize_extension' );
endif;