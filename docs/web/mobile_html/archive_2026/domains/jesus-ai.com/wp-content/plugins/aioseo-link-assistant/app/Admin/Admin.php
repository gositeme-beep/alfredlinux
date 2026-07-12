<?php
namespace AIOSEO\Plugin\Addon\LinkAssistant\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Addon\LinkAssistant\Models;

/**
 * Handles all admin logic.
 *
 * @since 1.0.0
 */
class Admin {
	/**
	 * Renders data for a column in the admin.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $columnName  The column name.
	 * @param  int    $postId      The current rows, post id.
	 * @param  array  $currentData The current column data.
	 * @return array               An array of associative data to be merged.
	 */
	public function renderColumnData( $columnName, $postId, $currentData = [] ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		if ( 'aioseo-details' !== $columnName || ! current_user_can( 'aioseo_page_link_assistant_settings' ) ) {
			return [];
		}

		return [
			'linkCounts' => [
				'inboundInternal'  => Models\Link::getTotalInboundInternalLinks( $postId ),
				'outboundInternal' => Models\Link::getTotalOutboundInternalLinks( $postId ),
				'affiliate'        => Models\Link::getTotalAffiliateLinks( $postId ),
				'external'         => Models\Link::getTotalExternalLinks( $postId )
			]
		];
	}
}