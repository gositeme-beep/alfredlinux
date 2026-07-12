<?php
namespace AIOSEO\Plugin\Addon\NewsSitemap\NewsSitemap;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parses the current request and checks whether we need to serve a sitemap or a stylesheet.
 *
 * @since 1.0.8
 */
class RequestParser {
	/**
	 * Checks whether we need to serve a sitemap or related stylesheet.
	 *
	 * @since 1.0.8
	 *
	 * @return void
	 */
	public function checkRequest() {
		if ( ! aioseo()->options->sitemap->news->enable ) {
			return;
		}

		$this->checkForXsl();

		if ( preg_match( '/^news-sitemap\.xml(\.gz)?$/i', aioseo()->sitemap->requestParser->slug, $match ) ) {
			aioseo()->sitemap->requestParser->setContext( 'news' );
			aioseo()->sitemap->generate();
		}
	}

	/**
	 * Checks if we need to serve a stylesheet.
	 *
	 * @since 1.0.8
	 *
	 * @return void
	 */
	protected function checkForXsl() {
		// Trim off the URL params.
		$slug = preg_replace( '/\?.*$/', '', aioseo()->sitemap->requestParser->slug );
		if ( preg_match( '/^news\.xsl$/i', $slug ) ) {
			aioseoNewsSitemap()->xsl->generate();
		}
	}
}