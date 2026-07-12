<?php
namespace AIOSEO\Plugin\Addon\LinkAssistant\Models;

use AIOSEO\Plugin\Common\Models as CommonModels;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Suggestion DB Model.
 *
 * @since 1.0.0
 */
class Suggestion extends CommonModels\Model {
	/**
	 * The name of the table in the database, without the prefix.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $table = 'aioseo_links_suggestions';

	/**
	 * Fields that should be numeric values.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $numericFields = [ 'id', 'post_id' ];

	/**
	 * Fields that should be JSON encoded.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $jsonFields = [ 'phrases' ];

	/**
	 * Appended as an extra column, but not stored in the DB.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $appends = [ 'context' ];

	/**
	 * Returns the inbound suggestions for the given post.
	 *
	 * @since 1.0.0
	 *
	 * @param  int   $postId The post ID.
	 * @param  int   $limit  The limit.
	 * @param  int   $offset The offset.
	 * @return array         The inbound suggestions.
	 */
	public static function getInboundSuggestions( $postId, $limit = 10, $offset = 0 ) {
		$postTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
		$postStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
		if ( empty( $postTypes ) || empty( $postStatuses ) ) {
			return [];
		}

		$query   = self::getBaseInboundSuggestionsQuery( $postId );
		$postIds = $query->select( 'als.post_id' )
			->limit( $limit, $offset )
			->run()
			->result();

		if ( empty( $postIds ) ) {
			return [];
		}

		$mappedPostIds = array_map( function ( $row ) {
			return $row->post_id;
		}, $postIds );

		$inboundSuggestions = aioseo()->core->db->start( 'aioseo_links_suggestions' )
			->where( 'linked_post_id', $postId )
			->whereIn( 'post_id', $mappedPostIds )
			->run()
			->models( 'AIOSEO\\Plugin\\Addon\\LinkAssistant\\Models\\Suggestion' );

		$targetPermalink = get_permalink( $postId );

		$groupedSuggestions = [];
		foreach ( $inboundSuggestions as $inboundSuggestion ) {
			if ( ! isset( $groupedSuggestions[ $inboundSuggestion->post_id ] ) ) {
				$groupedSuggestions[ $inboundSuggestion->post_id ] = [
					'suggestions' => [],
					'permalink'   => $targetPermalink
				];
			}

			$groupedSuggestions[ $inboundSuggestion->post_id ]['suggestions'][] = $inboundSuggestion;
		}

		$groupedSuggestionsWithContext = [];
		foreach ( $groupedSuggestions as $linkedPostId => $groupedSuggestion ) {
			$post = aioseo()->helpers->getPost( $linkedPostId );
			if ( ! is_a( $post, 'WP_Post' ) ) {
				continue;
			}

			$postTypeObject = get_post_type_object( get_post_type( $linkedPostId ) );

			$groupedSuggestion['context'] = [
				'postTitle' => aioseoLinkAssistant()->helpers->getPostTitle( $linkedPostId ),
				'permalink' => get_permalink( $linkedPostId ),
				'editLink'  => get_edit_post_link( $linkedPostId, '' ),
				'postType'  => aioseo()->helpers->getPostType( $postTypeObject )
			];

			$groupedSuggestionsWithContext[] = $groupedSuggestion;
		}

		return $groupedSuggestionsWithContext;
	}

	/**
	 * Returns the outbound suggestions for the given post.
	 *
	 * @since 1.0.0
	 *
	 * @param  int   $postId The post ID.
	 * @param  int   $limit  The limit.
	 * @param  int   $offset The offset.
	 * @return array         The outbound suggestions.
	 */
	public static function getOutboundSuggestions( $postId, $limit = 10, $offset = 0 ) {
		$postTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
		$postStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
		if ( empty( $postTypes ) || empty( $postStatuses ) ) {
			return [];
		}

		$query   = self::getBaseOutboundSuggestionsQuery( $postId );
		$phrases = $query->select( 'als.phrase' )
			->limit( $limit, $offset )
			->run()
			->result();

		if ( empty( $phrases ) ) {
			return [];
		}

		$mappedPhrases = array_map( function ( $row ) {
			return $row->phrase;
		}, $phrases );

		$outboundSuggestions = aioseo()->core->db->start( 'aioseo_links_suggestions' )
			->where( 'post_id', $postId )
			->whereIn( 'phrase', $mappedPhrases )
			->run()
			->models( 'AIOSEO\\Plugin\\Addon\\LinkAssistant\\Models\\Suggestion' );

		$groupedSuggestions = [];
		foreach ( $outboundSuggestions as $outboundSuggestion ) {
			if ( ! isset( $groupedSuggestions[ $outboundSuggestion->phrase ] ) ) {
				$groupedSuggestions[ $outboundSuggestion->phrase ] = [
					'suggestions' => []
				];
			}

			$postId = $outboundSuggestion->linked_post_id;

			$post = aioseo()->helpers->getPost( $postId );
			if ( ! is_a( $post, 'WP_Post' ) ) {
				continue;
			}

			// Update the outbound suggestion context.
			$outboundSuggestion->context            = new \stdClass();
			$outboundSuggestion->context->postTitle = aioseoLinkAssistant()->helpers->getPostTitle( $postId );
			$outboundSuggestion->context->permalink = get_permalink( $postId );
			$outboundSuggestion->context->editLink  = get_edit_post_link( $postId, '' );

			// Post Type data.
			$postTypeObject                         = get_post_type_object( get_post_type( $postId ) );
			$outboundSuggestion->context->postType  = aioseo()->helpers->getPostType( $postTypeObject );

			$groupedSuggestions[ $outboundSuggestion->phrase ]['suggestions'][] = $outboundSuggestion;
		}

		$numericGroupedSuggestions = array_values( $groupedSuggestions );

		return $numericGroupedSuggestions;
	}

	/**
	 * Returns the total amount of inbound suggestions for the given post.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $postId The post ID.
	 * @return int          The total amount of inbound suggestions.
	 */
	public static function getTotalInboundSuggestions( $postId ) {
		$postTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
		$postStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
		if ( empty( $postTypes ) || empty( $postStatuses ) ) {
			return 0;
		}

		$query = self::getBaseInboundSuggestionsQuery( $postId );

		return $query->count();
	}

	/**
	 * Returns the total amount of outbound suggestions for the given post.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $postId The post ID.
	 * @return int          The total amount of outbound suggestions.
	 */
	public static function getTotalOutboundSuggestions( $postId ) {
		$postTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
		$postStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
		if ( empty( $postTypes ) || empty( $postStatuses ) ) {
			return 0;
		}

		$query = self::getBaseOutboundSuggestionsQuery( $postId );

		return $query->count();
	}

	/**
	 * Returns the base query for inbound suggestions.
	 *
	 * @since 1.0.0
	 *
	 * @param  int      $postId The post ID.
	 * @return Database         The Database instance.
	 */
	private static function getBaseInboundSuggestionsQuery( $postId ) {
		$query = self::getBaseSuggestionsQuery();

		return $query->where( 'als.linked_post_id', $postId )
			->groupBy( 'als.post_id' );
	}

	/**
	 * Returns the base query for inbound suggestions.
	 *
	 * @since 1.0.0
	 *
	 * @param  int      $postId The post ID.
	 * @return Database         The Database instance.
	 */
	private static function getBaseOutboundSuggestionsQuery( $postId ) {
		$query = self::getBaseSuggestionsQuery();

		return $query
			->where( 'als.post_id', $postId )
			->groupBy( 'als.phrase' );
	}

	/**
	 * Returns the base query for suggestions.
	 *
	 * @since 1.0.0
	 *
	 * @return Database The Database instance.
	 */
	private static function getBaseSuggestionsQuery() {
		$postTypes            = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
		$postStatuses         = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
		$implodedPostTypes    = aioseo()->helpers->implodeWhereIn( $postTypes, true );
		$implodedPostStatuses = aioseo()->helpers->implodeWhereIn( $postStatuses, true );

		return aioseo()->core->db->start( 'aioseo_links_suggestions as als' )
			->join( 'posts as p', 'als.post_id = p.ID' )
			->whereRaw( "p.post_type IN ( $implodedPostTypes )" )
			->whereRaw( "p.post_status IN ( $implodedPostStatuses )" )
			->where( 'als.dismissed', 0 );
	}

	/**
	 * Returns the total amount of posts with suggestions.
	 *
	 * @since 1.0.0
	 *
	 * @return int The total amount of posts.
	 */
	public static function getTotalPosts() {
		static $totalPosts = null;
		if ( null === $totalPosts ) {
			$query = aioseo()->core->db->start( 'aioseo_links_suggestions as als' )
				->groupBy( 'als.post_id' );

			$postTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
			$postStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
			if ( empty( $postTypes ) || empty( $postStatuses ) ) {
				return 0;
			}

			$implodedPostTypes    = aioseo()->helpers->implodeWhereIn( $postTypes, true );
			$implodedPostStatuses = aioseo()->helpers->implodeWhereIn( $postStatuses, true );
			$query                = $query->join( 'posts as p', 'als.post_id = p.ID' )
				->whereRaw( "p.post_type IN ( $implodedPostTypes )" )
				->whereRaw( "p.post_status IN ( $implodedPostStatuses )" );

			$excludedPostIds = aioseoLinkAssistant()->helpers->getExcludedPostIds();
			if ( ! empty( $excludedPostIds ) ) {
				$implodedPostIds = aioseo()->helpers->implodeWhereIn( $excludedPostIds );
				$query = $query->whereRaw( "p.ID NOT IN ( $implodedPostIds )" );
			}
			$totalPosts = $query->count();
		}

		return $totalPosts;
	}

	/**
	 * Sanitizes the suggestion object.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $suggestion The suggestion data.
	 * @return array             The sanitized suggestion data.
	 */
	public static function sanitizeSuggestion( $suggestion ) {
		$sanitizedSuggestion = [];

		foreach ( $suggestion as $k => $v ) {
			switch ( $k ) {
				case 'post_id':
				case 'linked_post_id':
					$v = intval( $v );
					break;
				case 'anchor':
				case 'phrase':
				case 'paragraph':
					$v = sanitize_text_field( $v );
					break;
				case 'phrase_html':
				case 'paragraph_html':
				case 'original_phrase_html':
					$v = aioseoLinkAssistant()->helpers->wpKsesPhrase( $v );
					break;
				default:
					break;
			}

			if ( empty( $v ) ) {
				return [];
			}

			$sanitizedSuggestion[ $k ] = esc_sql( $v );
		}

		return $sanitizedSuggestion;
	}

	/**
	 * Checks whether the given suggestion is valid.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $suggestion The suggestion data.
	 * @return bool              Whether the suggestion is valid or not.
	 */
	public static function validateSuggestion( $suggestion ) {
		$propsToCheck = [
			'linked_post_id',
			'anchor',
			'phrase',
			'phrase_html',
			'original_phrase_html',
			'paragraph',
			'paragraph_html'
		];

		foreach ( $propsToCheck as $prop ) {
			$value = wp_strip_all_tags( $suggestion[ $prop ] );
			if ( empty( $value ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Dismisses the given suggestion.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $suggestionId The suggestion ID.
	 * @return void
	 */
	public static function dismissSuggestion( $suggestionId ) {
		$suggestion = aioseo()->core->db->start( 'aioseo_links_suggestions' )
			->where( 'id', $suggestionId )
			->run()
			->model( 'AIOSEO\\Plugin\\Addon\\LinkAssistant\\Models\\Suggestion' );

		if ( $suggestion->exists() ) {
			$suggestion->set( [ 'dismissed' => true ] );
			$suggestion->save();
		}
	}

	/**
	 * Deletes the given suggestion.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $suggestionId The Suggestion ID.
	 * @return void
	 */
	public static function deleteSuggestionById( $suggestionId ) {
		aioseo()->core->db->delete( 'aioseo_links_suggestions' )
			->where( 'id', $suggestionId )
			->run();
	}

	/**
	 * Deletes all non-dismissed suggestions for the given post.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $postsToScan The posts that we're about to scan.
	 * @return void
	 */
	public static function deleteNonDismissedSuggestions( $postsToScan ) {
		if ( ! is_array( $postsToScan ) ) {
			$postsToScan = [ $postsToScan ];
		}

		$postIds = array_map( function( $post ) {
			return $post->ID;
		}, $postsToScan );

		aioseo()->core->db->delete( 'aioseo_links_suggestions' )
			->whereIn( 'post_id', $postIds )
			->where( 'dismissed', 0 )
			->run();
	}
}