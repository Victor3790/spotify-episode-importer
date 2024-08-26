<?php
/**
 * Plugin Name: Podcast Importer
 * Description: Simple custom plugin to import podcast episodes
 * Author: Victor Crespo
 * Author URI: https://victorcrespo.net
 *
 * @package PodcastImporter
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

register_activation_hook( __FILE__, 'create_podcasts' );

/**
 * Get podcast episodes and create podcasts in WordPress.
 */
function create_podcasts() {

	// Create podcast
	$podcast_post_data = array(
		'post_title'   => 'Podcast episode one',
		'post_content' => 'Podcast description',
		'post_status'  => 'publish',
		'post_type'    => 'podcast',
	);
	$podcast_post_id = wp_insert_post( $podcast_post_data );

	// Add Spotify iframe
	update_post_meta( $podcast_post_id, 'podcast', 'podcast iframe' );

	// Set thumbnail
	$attachment = get_attachment();
	set_post_thumbnail( $podcast_post_id, $attachment->ID );
}

/**
 * Get the attachment by name
 */
function get_attachment() {

	$args = array(
		'posts_per_page' => 1,
		'post_type'      => 'attachment',
		'name'           => 'Next_Level-Podcast_Cover-FINAL',
	);

	$attachment = new WP_Query( $args );

	return $attachment->posts[0];
}
