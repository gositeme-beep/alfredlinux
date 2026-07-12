<?php
namespace AIOSEO\Plugin\Addon\Redirects\Utils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Addon\Redirects\Models;

/**
 * Contains helper functions
 *
 * @since 1.0.0
 */
class Helpers {
	/**
	 * Gets the data for vue.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $page The current page.
	 * @return array        An array of data.
	 */
	public function getVueData( $data = [], $page = null ) {
		if ( 'redirects' === $page ) {
			return $this->getRedirectsPageData( $data );
		}

		if ( 'tools' === $page ) {
			return $this->getToolsPageData( $data );
		}

		if ( 'post' === $page || ( ! empty( $data['currentPost']['context'] ) && 'term' === $data['currentPost']['context'] ) ) {
			return $this->getPostPageData( $data );
		}

		return $data;
	}

	/**
	 * Get posts page data.
	 *
	 * @since 1.1.7
	 *
	 * @param  array $data The data array.
	 * @return array       The modified data array.
	 */
	private function getPostPageData( $data ) {
		$data['redirects'] = [
			'options' => aioseoRedirects()->options->all(),
			'rows'    => array_values( aioseoRedirects()->redirect->getRedirects( $data['currentPost']['permalink'], 'all' ) )
		];

		return $data;
	}

	/**
	 * Get redirects page data.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $data The data array.
	 * @return array       The modified data array.
	 */
	private function getRedirectsPageData( $data ) {
		// Get the total number of results.
		$total     = aioseo()->core->db->start( 'aioseo_redirects' )->count();
		$total404  = aioseo()->core->db->start( 'aioseo_redirects_404_logs' )->groupBy( 'url' )->count();
		$totalLogs = aioseo()->core->db->start( 'aioseo_redirects_logs' )->groupBy( 'url' )->count();

		// Inject our vue data into this page.
		$wpUploadDir       = wp_upload_dir();
		$data['redirects'] = [
			'options'    => aioseoRedirects()->options->all(),
			'rows'       => array_values(
				aioseo()->core->db->start( 'aioseo_redirects' )
					->orderBy( 'id DESC' )
					->limit( aioseo()->settings->tablePagination['redirects'], 0 )
					->run()
					->models( 'AIOSEO\\Plugin\\Addon\\Redirects\\Models\\Redirect', null, true )
			),
			'logs404'    => Models\Redirects404Log::getFiltered( [ 'return' => 'results' ] ),
			'logs'       => Models\RedirectsLog::getFiltered( [ 'return' => 'results' ] ),
			'filters'    => [
				[
					'slug'   => 'all',
					'name'   => __( 'All', 'aioseo-redirects' ),
					'count'  => $total,
					'active' => true
				],
				[
					'slug'   => 'enabled',
					'name'   => __( 'Enabled', 'aioseo-redirects' ),
					'count'  => aioseo()->core->db->start( 'aioseo_redirects' )->where( 'enabled', 1 )->count(),
					'active' => false
				],
				[
					'slug'   => 'disabled',
					'name'   => __( 'Disabled', 'aioseo-redirects' ),
					'count'  => aioseo()->core->db->start( 'aioseo_redirects' )->where( 'enabled', 0 )->count(),
					'active' => false
				]
			],
			'totals'     => [
				'total404' => [
					'total' => $total404,
					'pages' => ceil( $total404 / aioseo()->settings->tablePagination['redirect404Logs'] ),
					'page'  => 1
				],
				'logs'     => [
					'total' => $totalLogs,
					'pages' => ceil( $totalLogs / aioseo()->settings->tablePagination['redirectLogs'] ),
					'page'  => 1
				],
				'main'     => [
					'total' => $total,
					'pages' => ceil( $total / aioseo()->settings->tablePagination['redirects'] ),
					'page'  => 1
				]
			],
			'path'       => $wpUploadDir['basedir'] . '/aioseo/redirects/.redirects',
			'importers'  => aioseoRedirects()->importExport->plugins(),
			'server'     => [
				'redirectTest' => [
					'testing' => false,
					'failed'  => aioseoRedirects()->cache->get( 'server-redirects-failed' )
				]
			],
			'manualUrls' => []
		];

		if ( ! empty( $_GET['aioseo-manual-urls'] ) ) {
			$data['redirects']['manualUrls'] = aioseoRedirects()->helpers->getManualRedirectUrls( sanitize_key( $_GET['aioseo-manual-urls'] ) ) ?: [];
		}

		$data['data']['server'] = [
			'apache' => aioseo()->helpers->isApache(),
			'nginx'  => aioseo()->helpers->isNginx()
		];

		return $data;
	}

	/**
	 * Get tools page data.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $data The data array.
	 * @return array       The modified data array.
	 */
	private function getToolsPageData( $data ) {
		$data['data']['logSizes']['logs404']      = aioseo()->helpers->convertFileSize( aioseo()->core->db->getTableSize( 'aioseo_redirects_404_logs' ) );
		$data['data']['logSizes']['redirectLogs'] = aioseo()->helpers->convertFileSize( aioseo()->core->db->getTableSize( 'aioseo_redirects_logs' ) );

		return $data;
	}


	/**
	 * Generates a URL for adding manual redirects.
	 *
	 * @since 1.2.1
	 *
	 * @param  array  $urls An array of [url, target, type, slash, case, regex].
	 * @return string       The redirect link.
	 */
	public function manualRedirectUrl( $urls ) {
		// Transform a single url on an array of urls.
		if ( isset( $urls['url'] ) ) {
			$urls = [ $urls ];
		}

		// Cleanup urls.
		foreach ( $urls as &$url ) {
			$url['url'] = WpUri::excludeHomeUrl( $url['url'] );
		}

		$hash = md5( wp_json_encode( $urls ) );

		aioseoRedirects()->cache->update( 'manual-urls-' . $hash, $urls, HOUR_IN_SECONDS );

		return add_query_arg( 'aioseo-manual-urls', $hash, admin_url( 'admin.php?page=aioseo-redirects' ) );
	}

	/**
	 * Gets stored manual redirects.
	 *
	 * @since 1.2.2
	 *
	 * @param  string $hash The hash to search for.
	 * @return string       The stored manual redirects.
	 */
	public function getManualRedirectUrls( $hash ) {
		return aioseoRedirects()->cache->get( 'manual-urls-' . $hash );
	}

	/**
	 * Does a 404 redirect.
	 *
	 * @since 1.2.2
	 *
	 * @param  string $url The url to redirect to.
	 * @return void
	 */
	public function do404Redirect( $url, $redirectBy = 'AIOSEO 404' ) {
		if ( 'AIOSEO 404' !== $redirectBy ) {
			// Prefix redirect by.
			$redirectBy = ucwords( strtolower( str_replace( '-', ' ', $redirectBy ) ) );
			$redirectBy = 'AIOSEO 404 ' . $redirectBy;
		}

		// Safe redirect.
		wp_safe_redirect( $url, 301, $redirectBy );
		die;
	}

	/**
	 * Takes an array of url parts and puts them together with proper slashes.
	 *
	 * @since 1.1.0
	 *
	 * @param  array  $parts URL parts in order.
	 * @return string        A built URL.
	 */
	public function makeUrl( $parts ) {
		if ( ! is_array( $parts ) ) {
			$parts = [ $parts ];
		}

		$url = implode( '', array_map( 'trailingslashit', $parts ) );

		// Make sure http(s) has '://'.
		$url = preg_replace( '@^(http(?:s|))(?:://|:/|:|)@', '$1://', $url );

		// Fix duplicated forward slashes.
		return preg_replace( '/(?<!http:|https:)\/{2,}/', '/', $url );
	}
}