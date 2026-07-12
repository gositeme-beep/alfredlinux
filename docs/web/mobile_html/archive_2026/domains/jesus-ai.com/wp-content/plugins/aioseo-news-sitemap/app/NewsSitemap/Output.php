<?php
namespace AIOSEO\Plugin\Addon\NewsSitemap\NewsSitemap;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles outputting the sitemap.
 *
 * @since 1.0.0
 */
class Output {
	/**
	 * Outputs the sitemap.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $entries The sitemap entries.
	 * @return void
	 */
	public function output( $entries ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		if ( 'news' !== aioseo()->sitemap->type ) {
			return;
		}

		$xslUrl     = add_query_arg( 'news-sitemap', aioseo()->sitemap->indexName, home_url() . '/news.xsl' );
		$charset    = aioseo()->helpers->getCharset();
		$generation = __( 'dynamically', 'aioseo-news-sitemap' );

		echo '<?xml version="1.0" encoding="' . esc_attr( $charset ) . "\"?>\r\n";
		echo '<!-- ' . sprintf(
			// Translators: 1 - "statically" or "dynamically", 2 - The date, 3 - The time, 4 - The plugin name ("All in One SEO").
			esc_html__( 'This sitemap was %1$s generated on %2$s at %3$s by %4$s - the original SEO plugin for WordPress.', 'all-in-one-seo-pack' ),
			esc_html( $generation ),
			esc_html( date_i18n( get_option( 'date_format' ) ) ),
			esc_html( date_i18n( get_option( 'time_format' ) ) ),
			esc_html( AIOSEO_PLUGIN_NAME )
		) . ' -->';

		echo "\r\n\r\n<?xml-stylesheet type=\"text/xsl\" href=\"" . esc_attr( $xslUrl ) . "\"?>\r\n";
		include_once( AIOSEO_NEWS_SITEMAP_DIR . '/app/Views/xml/news.php' );
	}
}