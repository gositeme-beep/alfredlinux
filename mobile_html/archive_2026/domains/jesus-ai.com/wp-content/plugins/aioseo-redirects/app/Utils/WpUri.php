<?php
namespace AIOSEO\Plugin\Addon\Redirects\Utils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helpers for the WP urls.
 *
 * @since 1.2.3
 */
class WpUri {
	/**
	 * Returns a post url path without the home path.
	 *
	 * @since 1.2.3
	 *
	 * @param  string $postId The post id.
	 * @return string         The path without WP's home path.
	 */
	public static function getPostPath( $postId ) {
		return self::getUrlPath( get_permalink( $postId ) );
	}

	/**
	 * Returns an url path without the home path.
	 *
	 * @since 1.2.3
	 *
	 * @param  string $url The url.
	 * @return string      The path without WP's home path.
	 */
	public static function getUrlPath( $url ) {
		return self::excludeHomePath( wp_parse_url( $url, PHP_URL_PATH ) );
	}

	/**
	 * Exclude the home path from a full path.
	 *
	 * @since 1.2.3
	 *
	 * @param  string $path The original path.
	 * @return string       The path without WP's home path.
	 */
	public static function excludeHomePath( $path ) {
		return preg_replace( '@^' . untrailingslashit( aioseo()->helpers->getHomePath() ) . '(/|$)@', '/', $path );
	}

	/**
	 * Exclude the home url from a full url.
	 *
	 * @since 1.2.3
	 *
	 * @param  string $url The original url.
	 * @return string      The url without WP's home url.
	 */
	public static function excludeHomeUrl( $url ) {
		return aioseo()->helpers->getPermalinkPath( $url );
	}
}