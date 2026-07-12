<?php
namespace AIOSEO\Plugin\Addon\LinkAssistant\Traits;

use AIOSEO\Plugin\Addon\LinkAssistant\Models;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles all data Vue needs for the Link Assistant metabox.
 *
 * @since 1.0.0
 */
trait PostSettings {
	/**
	 * Returns the data for the metabox.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $data   The data.
	 * @paramm int   $postId The post ID.
	 * @return array         The modified data.
	 */
	public function getPostData( $data = [], $postId = 0 ) {
		$postId = $postId ? $postId : get_the_ID();
		if ( ! $postId ) {
			return $data;
		}

		// We don't need to perform the queries on Page Builders since Link Assistant is disabled there.
		if ( ! empty( $data['integration'] ) ) {
			return $data;
		}

		$data['linkAssistant'] = [
			'options'         => aioseoLinkAssistant()->options->all(),
			'internalOptions' => aioseoLinkAssistant()->internalOptions->all()
		];

		$data['currentPost']['linkAssistant'] = [
			'isExcludedPost'  => $this->isExcludedPost( $postId ),
			'links'           => $this->getPostLinks( $postId, null ),
			'totals'          => $this->getPostTotals(),
			'suggestionsScan' => [
				'percent' => aioseoLinkAssistant()->helpers->getSuggestionsScanPercent(),
			]
		];

		return $data;
	}

	/**
	 * Returns the link/suggestion/post totals.
	 *
	 * @since 1.0.0
	 *
	 * @return array The totals.
	 */
	private function getPostTotals() {
		$totals = [
			'suggestions'    => (int) Models\Suggestion::getTotalPosts(),
			'externalLinks'  => (int) Models\Link::getSiteTotalExternalLinks(),
			'internalLinks'  => (int) Models\Link::getSiteTotalInternalLinks(),
			'affiliateLinks' => (int) Models\Link::getSiteTotalAffiliateLinks(),
			'totalLinks'     => (int) Models\Link::getSiteTotalLinks()
		];

		$postTotals = [];
		foreach ( $totals as $k => $v ) {
			$postTotals[ $k ] = [
				'total' => $v
			];
		}

		return $postTotals;
	}

}