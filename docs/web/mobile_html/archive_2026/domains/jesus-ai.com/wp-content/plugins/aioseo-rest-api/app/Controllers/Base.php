<?php
namespace AIOSEO\Plugin\Addon\RestApi\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Common\Meta;

/**
 * Base class for the post/term controller.
 *
 * @since 1.0.0
 */
abstract class Base {
	/**
	 * The original main query.
	 *
	 * @since 1.0.0
	 *
	 * @var WP_Query
	 */
	protected $originalQuery;

	/**
	 * Internally used fields that we can strip from the data that we return to the user.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $internalFields = [
		'page_analysis',
		'seo_score',
		'images',
		'image_scan_date',
		'videos',
		'video_thumbnail',
		'video_scan_date',
		'link_scan_date',
		'link_suggestions_scan_date',
		'options'
	];

	/**
	 * The deprecated fields from V3.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $deprecatedFields = [
		'_aioseop_title'       => 'title',
		'_aioseop_description' => 'description'
	];

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register' ] );
	}

	/**
	 * Registers the HEAD fields.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $object The object (post type or taxonomy).
	 * @return void
	 */
	protected function registerHeadFields( $object ) {
		if ( ! apply_filters( 'aioseo_rest_api_disable_head', false, $object ) ) {
			register_rest_field( $object, 'aioseo_head', [
				'get_callback' => [ $this, 'getHead' ]
			] );
		}

		if ( ! apply_filters( 'aioseo_rest_api_disable_head_json', false, $object ) ) {
			register_rest_field( $object, 'aioseo_head_json', [
				'get_callback' => [ $this, 'getHeadJson' ]
			] );
		}
	}

	/**
	 * Returns the raw data that we would output in the HEAD for the given object.
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $object The object (post type or taxonomy).
	 * @return string         The raw HEAD data.
	 */
	public function getHead( $object ) {
		if ( apply_filters( 'aioseo_rest_api_disable', false, $object ) ) {
			return '';
		}

		$this->setWpQuery( $object );

		ob_start();
		aioseo()->head->output();
		$output = ob_get_clean();

		// Add the title tag to our own comment block.
		$pageTitle = aioseo()->helpers->escapeRegexReplacement( aioseo()->head->getTitle() );
		$output    = preg_replace( '#(<!--\sAll\sin\sOne\sSEO[a-zA-Z\s0-9.]+\s-->)#', "$1\r\n\t\t<title>$pageTitle</title>", $output, 1 );

		$this->restoreWpQuery();

		return $output;
	}

	/**
	 * Returns the data that we would output in the HEAD for the given object, but as JSON.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $object The object (post type or taxonomy).
	 * @return array         The data (will be JSONified by the WP REST API class).
	 */
	public function getHeadJson( $object ) {
		if ( apply_filters( 'aioseo_rest_api_disable', false, $object ) ) {
			return [];
		}

		$this->setWpQuery( $object );

		$keywordsInstance = new Meta\Keywords;
		$keywords         = $keywordsInstance->getKeywords();

		$siteVerificationInstance        = new Meta\SiteVerification;
		$webmasterTools                  = $siteVerificationInstance->meta();
		$miscellaneous                   = aioseo()->options->webmasterTools->miscellaneousVerification;
		$webmasterTools['miscellaneous'] = trim( $miscellaneous );

		$data = [
			'title'          => aioseo()->meta->title->getTitle(),
			'description'    => aioseo()->meta->description->getDescription(),
			'canonical_url'  => aioseo()->helpers->canonicalUrl(),
			'robots'         => aioseo()->meta->robots->meta(),
			'keywords'       => $keywords,
			'webmasterTools' => $webmasterTools
		];

		$data = array_merge(
			$data,
			aioseo()->social->output->getFacebookMeta(),
			aioseo()->social->output->getTwitterMeta()
		);

		$data['schema'] = aioseo()->options->searchAppearance->advanced->sitelinks
			? json_decode( aioseo()->schema->get() )
			: '';

		$this->restoreWpQuery();

		return $data;
	}

	/**
	 * Checks whether the current user can edit any of our meta data.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the current user is allowed to edit any of our meta data.
	 */
	protected function canEditMetaData() {
		return (
			current_user_can( 'aioseo_page_general_settings' ) ||
			current_user_can( 'aioseo_page_social_settings' ) ||
			current_user_can( 'aioseo_page_schema_settings' ) ||
			current_user_can( 'aioseo_page_advanced_settings' )
		);
	}

	/**
	 * Removes internal fields from the meta data.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $data The data.
	 * @return array       The modified data.
	 */
	protected function removeInternalFields( $data ) {
		foreach ( $this->internalFields as $internalField ) {
			unset( $data[ $internalField ] );
		}

		return $data;
	}

	/**
	 * Sets the given object as the queried object of the main query.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $post The post object.
	 * @return void
	 */
	protected abstract function setWpQuery( $object );

	/**
	 * Restores the main query back to the original query.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function restoreWpQuery() {
		if ( null === $this->originalQuery ) {
			return;
		}

		global $wp_query;
		$wp_query = clone $this->originalQuery;

		$this->originalQuery = null;
	}
}