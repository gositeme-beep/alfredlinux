<?php
namespace AIOSEO\Plugin\Addon\LinkAssistant\Traits;

use AIOSEO\Plugin\Addon\LinkAssistant\Models;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles all data Vue needs for the Overview page.
 *
 * @since 1.0.0
 */
trait Overview {
	/**
	 * Returns all data for the Overview page.
	 *
	 * @since 1.0.0
	 *
	 * @return array The data.
	 */
	public function getOverviewData() {
		return [
			'totals'               => $this->getLinkCountTotals(),
			'mostLinkedDomains'    => $this->getMostLinkedDomains(),
			'linkingOpportunities' => $this->getLinkingOpportunities()
		];
	}

	/**
	 * Returns the totals for the Link Count bar at the top of the Overview page.
	 *
	 * @since 1.0.0
	 *
	 * @return array The totals.
	 */
	private function getLinkCountTotals() {
		return [
			'crawledPosts'   => (int) $this->getTotalCrawledPosts(),
			'orphanedPosts'  => (int) $this->getTotalOrphanedPosts(),
			'externalLinks'  => (int) Models\Link::getSiteTotalExternalLinks(),
			'internalLinks'  => (int) Models\Link::getSiteTotalInternalLinks(),
			'affiliateLinks' => (int) Models\Link::getSiteTotalAffiliateLinks(),
			'totalLinks'     => (int) Models\Link::getSiteTotalLinks()
		];
	}

	/**
	 * Returns the top 10 most linked to external domains.
	 *
	 * @since 1.0.0
	 *
	 * @return array The most linked domains.
	 */
	private function getMostLinkedDomains() {
		$postTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
		$postStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
		if ( empty( $postTypes ) || empty( $postStatuses ) ) {
			return [];
		}

		$implodedPostTypes    = aioseo()->helpers->implodeWhereIn( $postTypes, true );
		$implodedPostStatuses = aioseo()->helpers->implodeWhereIn( $postStatuses, true );

		return aioseo()->core->db->start( 'aioseo_links as al' )
			->select( 'al.hostname as name, count(*) as count' )
			->join( 'posts as p', 'al.post_id = p.ID' )
			->whereRaw( "p.post_type IN ( $implodedPostTypes )" )
			->whereRaw( "p.post_status IN ( $implodedPostStatuses )" )
			->where( 'al.external', 1 )
			->groupBy( 'name' )
			->orderBy( 'count DESC' )
			->limit( 10 )
			->run()
			->result();
	}

	/**
	 * Returns the 5 posts with the most link suggestions.
	 * The query favours orphaned posts with the highest amount of inbound suggestions.
	 *
	 * @since 1.0.0
	 *
	 * @return array The posts.
	 */
	private function getLinkingOpportunities() {
		$includedPostTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
		$includedPostStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
		if ( empty( $includedPostTypes ) || empty( $includedPostStatuses ) ) {
			return [];
		}

		$postTypePlaceholders   = implode( ', ', array_fill( 0, count( $includedPostTypes ), '%s' ) );
		$postStatusPlaceholders = implode( ', ', array_fill( 0, count( $includedPostStatuses ), '%s' ) );

		$suggestionsTableName = aioseo()->core->db->prefix . 'aioseo_links_suggestions';
		$postsTableName       = aioseo()->core->db->prefix . 'posts';

		$posts = aioseo()->core->db->execute(
			aioseo()->core->db->db->prepare(
				"SELECT als2.post_id,
				(
					SELECT COUNT( DISTINCT als3.phrase )
					FROM $suggestionsTableName as als3
					JOIN $postsTableName as p ON als3.post_id = p.ID
					WHERE p.post_type IN ( $postTypePlaceholders )
					AND p.post_status IN ( $postStatusPlaceholders )
					AND als2.post_id = als3.post_id
				) as suggestionsOutbound,
				(
					SELECT COUNT( DISTINCT als4.post_id )
					FROM $suggestionsTableName as als4
					JOIN $postsTableName as p ON als4.post_id = p.ID
					WHERE p.post_type IN ( $postTypePlaceholders )
					AND p.post_status IN ( $postStatusPlaceholders )
					AND als2.post_id = als4.linked_post_id AND als4.post_id != als2.post_id
				) as suggestionsInbound
				FROM $suggestionsTableName as als2
				GROUP BY als2.post_id
				ORDER BY suggestionsInbound DESC
				LIMIT 5",
				array_merge(
					$includedPostTypes,
					$includedPostStatuses,
					$includedPostTypes,
					$includedPostStatuses
				)
			),
			true
		)->result();

		foreach ( $posts as $post ) {
			$post->postTitle = aioseoLinkAssistant()->helpers->getPostTitle( $post->post_id );
			$post->permalink = get_permalink( $post->post_id );
		}

		return $posts;
	}

	/**
	 * Returns the total amount of orphaned posts.
	 *
	 * @since 1.0.0
	 *
	 * @return int The total amount of orphaned posts.
	 */
	private function getTotalOrphanedPosts() {
		static $totalOrphanedPosts = null;
		if ( null === $totalOrphanedPosts ) {
			$totalCrawledPosts  = $this->getTotalCrawledPosts();
			$totalLinkedPosts   = Models\Link::getSiteTotalLinkedPosts();
			$totalOrphanedPosts = $totalCrawledPosts - $totalLinkedPosts;
		}

		return $totalOrphanedPosts;
	}

	/**
	 * Returns the total amount of crawled posts.
	 *
	 * @since 1.0.0
	 *
	 * @return int The total amount of crawled posts.
	 */
	private function getTotalCrawledPosts() {
		static $totalCrawledPosts = null;
		if ( null === $totalCrawledPosts ) {
			$query = aioseo()->core->db->start( 'aioseo_posts as ap' )
				->whereRaw( 'ap.link_scan_date IS NOT NULL' );

			$postTypes    = $this->getIncludedPostTypes();
			$postStatuses = $this->getIncludedPostStatuses();
			if ( empty( $postTypes ) || empty( $postStatuses ) ) {
				return 0;
			}

			$implodedPostTypes    = aioseo()->helpers->implodeWhereIn( $postTypes, true );
			$implodedPostStatuses = aioseo()->helpers->implodeWhereIn( $postStatuses, true );
			$query                = $query->join( 'posts as p', 'ap.post_id = p.ID' )
				->whereRaw( "p.post_type IN ( $implodedPostTypes )" )
				->whereRaw( "p.post_status IN ( $implodedPostStatuses )" );

			$excludedPostIds = $this->getExcludedPostIds();
			if ( ! empty( $excludedPostIds ) ) {
				$implodedPostIds = aioseo()->helpers->implodeWhereIn( $excludedPostIds );
				$query = $query->whereRaw( "p.ID NOT IN ( $implodedPostIds )" );
			}

			$totalCrawledPosts = $query->count();
		}

		return $totalCrawledPosts;
	}
}