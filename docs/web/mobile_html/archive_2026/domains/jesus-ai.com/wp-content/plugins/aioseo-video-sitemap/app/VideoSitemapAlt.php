<?php
namespace AIOSEO\Plugin\Addon\VideoSitemap\VideoSitemap;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alternate class to fix update bug. This class is designed to return empty methods and fail.
 *
 * @since 1.0.3
 */
class Video {
	/**
	 * Scans a given post for videos.
	 *
	 * @since 1.0.0
	 *
	 * @param  mixed $post The post object or ID.
	 * @return void
	 */
	public function scanPost() {}

	/**
	 * Scans a given term for videos.
	 *
	 * @since 1.0.0
	 *
	 * @param  mixed $term The term object or ID.
	 * @return void
	 */
	public function scanTerm() {}
}