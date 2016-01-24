<?php

/*
 * Plugin Name: No wpautop
 * Plugin URI:
 * Description: Disables wpautop.
 * Version:     0.1.0
 * Author:      Ella Iseulde Van Dorpe
 * Author URI:  https://iseulde.com
 * Text Domain:
 * Domain Path:
 * Network:
 * License:     GPL-2.0+
 */

include ABSPATH . WPINC . '/version.php';

defined( 'ABSPATH' ) ||
version_compare( PHP_VERSION, '5.3.0', '>=' ) ||
version_compare( rtrim( $wp_version, '-src' ), '4.4', '>=' ) ||
	die;

// Every time a post is saved (manually) since activation, we know it no longer needs `wpautop`.
add_action( 'save_post', function ( $id ) {
	! empty( $_POST ) && update_post_meta( $id, 'no-auto-p', '1' );
} );

// Posts after deactivation might be saved again and may need `wpautop`, so this is no longer reliable.
register_deactivation_hook( __FILE__, function() {
	delete_post_meta_by_key( 'no-auto-p' );
} );

// Remove the `wpautop` filter from posts that no longer need it, and fix auto embedding.
add_filter( 'the_content', function( $content ) {
	if ( get_post_meta( $GLOBALS['post']->ID, 'no-auto-p', true ) === '1' ) {
		remove_filter( 'the_content', array( $GLOBALS['wp_embed'], 'autoembed' ), 8 );
		remove_filter( 'the_content', 'wpautop' );

		add_filter( 'the_content', function( $content ) {
			$content = preg_replace_callback( '|<p[^>]*>(\s*)(https?://[^\s<]+)(\s*)<\/p>|i', array( $GLOBALS['wp_embed'], 'autoembed_callback' ), $content );

			return $content;
		}, 8 );
	}

	return $content;
}, 7 );

// Disable `autop` and `removep` when switching editors.
add_filter( 'wp_editor_settings', function( $settings, $id ) {
	$GLOBALS['no_auto_p_current_editor'] = $id;

	if ( $id === 'content' ) {
		$settings['wpautop'] = false;
	}

	return $settings;
}, 10, 2 );

// If a post still needs `wpautop`, run it before showing the content in the textarea.
add_filter( 'the_editor_content', function( $content ) {
	if ( $GLOBALS['no_auto_p_current_editor'] === 'content' && get_post_meta( $_GET['post'], 'no-auto-p', true ) !== '1' ) {
		$content = wpautop( $content );
	}

	return $content;
} );
