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

	$episodes = get_episodes( 50, 50 );

	if ( ! $episodes ) {
		return;
	}

	$minutes_ahead = 1;

	foreach ( $episodes as $episode ) {

		$post_date = get_post_date( $minutes_ahead );
		create_podcast( $episode, $post_date );
		$minutes_ahead++;

	}

	/** Second import */
	$episodes = get_episodes( 50, 0 );

	if ( ! $episodes ) {
		return;
	}

	foreach ( $episodes as $episode ) {

		$post_date = get_post_date( $minutes_ahead );
		create_podcast( $episode, $post_date );
		$minutes_ahead++;

	}

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

/**
 * Get podcast episodes from Spotify
 *
 * @param int $limit The limit of episodes to get.
 * @param int $offset The offset to start getting episodes.
 */
function get_episodes( $limit, $offset ) {

	$token = 'add token here';

	// Get podcast episodes.
	$wp_http = new WP_Http();

	$episode_data = $wp_http->get(
		'https://api.spotify.com/v1/shows/show_id/episodes?offset=' . $offset . '&limit=' . $limit,
		array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
			),
		)
	);

	$array_episode_data = json_decode( $episode_data['body'], true );

	if ( ! isset( $array_episode_data['items'] ) ) {
		return false;
	}

	return array_reverse( $array_episode_data['items'] );

}

/**
 * Create the podcast post.
 *
 * @param array  $episode The episode data to post.
 * @param string $post_date The publish date to add.
 */
function create_podcast( $episode, $post_date ) {

	// Format the description.
	$description = format_html_description( $episode['html_description'] );

	// Create podcast.
	$podcast_post_data = array(
		'post_title'   => $episode['name'],
		'post_content' => $description,
		'post_status'  => 'publish',
		'post_type'    => 'podcast',
		'post_date'    => $post_date,
	);

	$podcast_post_id = wp_insert_post( $podcast_post_data );

	// Get podcast iframe.
	$iframe = get_iframe( $episode['id'] );

	// Add Spotify iframe.
	update_post_meta( $podcast_post_id, 'podcast', $iframe );

	// Set thumbnail.
	$attachment = get_attachment();
	set_post_thumbnail( $podcast_post_id, $attachment->ID );

}

/**
 * Remove the credits contained in the last four paragraphs of the description.
 * Add h2 subtitles
 * Add target blank attribute to links.
 *
 * @param string $description The HTML description from Spotify.
 */
function format_html_description( $description ) {

	$dom = new DOMDocument();

	$dom->encoding = 'utf-8';
	$dom->loadHTML(
		mb_convert_encoding( $description, 'HTML-ENTITIES', 'UTF-8' ),
		LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
	);
	$xpath = new DOMXPath( $dom );

	// Remove credits.
	$episode_credits_title = $xpath->query( "//p[contains(text(), 'Episode Credits')]" )->item( 0 );

	if ( ! is_null( $episode_credits_title ) ) {
		//phpcs:ignore
		$episode_credits_title->parentNode->removeChild( $episode_credits_title );
	}

	$episode_credits_text = $xpath->query( "//p[contains(text(), 'If you like this podcast and are thinking of creating your own,')]" )->item( 0 );

	if ( ! is_null( $episode_credits_text ) ) {
		//phpcs:ignore
		$episode_credits_text->parentNode->removeChild( $episode_credits_text );
	}

	$episode_credits_asterisks = $xpath->query( "//p[text()='***']" )->item( 0 );

	if ( ! is_null( $episode_credits_asterisks ) ) {
		//phpcs:ignore
		$episode_credits_asterisks->parentNode->removeChild( $episode_credits_asterisks );
	}

	// Add headers.
	$show_highlights = $xpath->query( "//p[contains(text(), 'Show Highlights')]" )->item( 0 );

	if ( ! is_null( $show_highlights ) ) {
		$h2_show_highlights = $dom->createElement( 'h2' );
		//phpcs:ignore
		$h2_show_highlights->appendChild( $dom->createTextNode( $show_highlights->textContent ) );
		//phpcs:ignore
		$show_highlights->parentNode->replaceChild( $h2_show_highlights, $show_highlights );
	}

	$subscribe_review = $xpath->query( "//p[contains(text(), 'Subscribe and Review') or contains(text(), 'Follow and Review')]" )->item( 0 );

	if ( ! is_null( $subscribe_review ) ) {
		$h2_subscribe_review = $dom->createElement( 'h2' );
		//phpcs:ignore
		$h2_subscribe_review->appendChild( $dom->createTextNode( $subscribe_review->textContent ) );
		//phpcs:ignore
		$subscribe_review->parentNode->replaceChild( $h2_subscribe_review, $subscribe_review );
	}

	$supporting_resources = $xpath->query( "//p[contains(text(), 'Supporting Resources')]" )->item( 0 );

	if ( ! is_null( $supporting_resources ) ) {
		$h2_supporting_resources = $dom->createElement( 'h2' );
		//phpcs:ignore
		$h2_supporting_resources->appendChild( $dom->createTextNode( $supporting_resources->textContent ) );
		//phpcs:ignore
		$supporting_resources->parentNode->replaceChild( $h2_supporting_resources, $supporting_resources );
	}

	$action_steps = $xpath->query( "//p[contains(text(), 'Action Steps') or contains(text(), 'ACTION STEPS')]" )->item( 0 );

	if ( ! is_null( $action_steps ) ) {
		$h2_action_steps = $dom->createElement( 'h2' );
		//phpcs:ignore
		$h2_action_steps->appendChild( $dom->createTextNode( $action_steps->textContent ) );
		//phpcs:ignore
		$action_steps->parentNode->replaceChild( $h2_action_steps, $action_steps );
	}

	$more_info = $xpath->query( "//p[contains(text(), 'More Information About Our Hosts')]" )->item( 0 );

	if ( ! is_null( $more_info ) ) {
		$h2_more_info = $dom->createElement( 'h2' );
		//phpcs:ignore
		$h2_more_info->appendChild( $dom->createTextNode( $more_info->textContent ) );
		//phpcs:ignore
		$more_info->parentNode->replaceChild( $h2_more_info, $more_info );
	}

	// Add target and rel attributes to links.
	$anchors = $dom->getElementsByTagName( 'a' );

	foreach ( $anchors as $anchor ) {

		if ( $anchor->hasAttribute( 'href' ) && strpos( $anchor->getAttribute( 'href' ), 'gngf.com' ) !== false ) {
			continue;
		}

		if ( ! $anchor->hasAttribute( 'target' ) ) {
			$anchor->setAttribute( 'target', '_blank' );
		}

		if ( ! $anchor->hasAttribute( 'rel' ) ) {
			$anchor->setAttribute( 'rel', 'noopener noreferrer' );
		} elseif ( ! $anchor->getAttribute( 'rel' ) === 'noopener' ) {
			$anchor->setAttribute( 'rel', 'noopener noreferrer' );
		}
	}

	return $dom->saveHtml();

}

/**
 * Get the Spotify podcast episode iframe
 *
 * @param string $episode_id the id of the episode to add to ifram code.
 */
function get_iframe( $episode_id ) {

	$iframe_html = '<iframe style="border-radius: 12px;" src="https://open.spotify.com/embed/episode/spotify_episode_id?utm_source=generator&amp;theme=0" width="100%" height="152" frameborder="0" allowfullscreen="allowfullscreen"></iframe>';

	return str_replace( 'spotify_episode_id', $episode_id, $iframe_html );

}

/**
 * Get the correct post date, we'll consider last week plus x minutes.
 *
 * @param int $minutes_ahead number of minutes to add.
 */
function get_post_date( $minutes_ahead ) {

	$today = new DateTime();

	$last_monday = clone $today;
	$last_monday->modify( 'last tuesday' );

	$next_minute = clone $last_monday;
	$next_minute->modify( '+' . $minutes_ahead . ' minute' );

	return $next_minute->format( 'Y-m-d H:i:s' );

}
