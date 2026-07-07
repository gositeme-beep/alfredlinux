<?php
namespace AIOSEO\Plugin\Addon\VideoSitemap\VideoSitemap;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles our sitemap search engine ping feature.
 *
 * @since 1.0.0
 */
class Ping {
	/**
	 * Returns the URLs that should be sent to search engines for discovery.
	 *
	 * @since 1.0.0
	 *
	 * @return array $sitemapUrls Sitemap URLs that should be sent to the remote endpoints.
	 */
	public function getPingUrls() {
		$sitemapUrls = [];
		if ( aioseo()->options->sitemap->video->enable ) {
			$sitemapUrls[] = trailingslashit( home_url() ) . aioseo()->options->sitemap->video->filename . '.xml';
		}

		return $sitemapUrls;
	}
}