<?php
namespace AIOSEO\Plugin\Addon\LinkAssistant\Models;

use AIOSEO\Plugin\Common\Models as CommonModels;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Link DB Model.
 *
 * @since 1.0.0
 */
class Link extends CommonModels\Model {
	/**
	 * The name of the table in the database, without the prefix.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $table = 'aioseo_links';

	/**
	 * Fields that should be numeric values.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $numericFields = [ 'id', 'post_id', 'linked_post_id' ];

	/**
	 * Fields that are nullable.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $nullFields = [ 'linked_post_id' ];

	/**
	 * Fields that should be boolean values.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $booleanFields = [
		'internal',
		'affiliate',
		'external'
	];

	/**
	 * Appended as an extra column, but not stored in the DB.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $appends = [ 'context' ];

	/**
	 * Returns the Link with the given ID.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $linkId The Link ID.
	 * @return Link         The Link.
	 */
	public static function getLinkById( $linkId ) {
		return aioseo()->core->db->start( 'aioseo_links' )
			->where( 'id', $linkId )
			->run()
			->model( 'AIOSEO\\Plugin\\Addon\\LinkAssistant\\Models\\Link' );
	}

	/**
	 * Returns all links for the given post.
	 *
	 * @since 1.0.0
	 *
	 * @param  int         $postId      The post ID.
	 * @param  string      $whereClause An optional WHERE clause for search queries.
	 * @return array[Link]              The Links.
	 */
	public static function getLinks( $postId, $whereClause = null ) {
		$query = aioseo()->core->db->start( 'aioseo_links' )
			->where( 'post_id', $postId );

		if ( $whereClause ) {
			$query = $query->whereRaw( $whereClause );
		}

		return $query->run()
			->models( 'AIOSEO\\Plugin\\Addon\\LinkAssistant\\Models\\Link' );
	}

	/**
	 * Returns inbound internal links that refer to the given post.
	 *
	 * @since 1.0.0
	 *
	 * @param  int         $linkedPostId The ID of the post the Link refers to.
	 * @param  int         $limit        The limit.
	 * @param  int         $offset       The offset.
	 * @return array[Link]               The Links.
	 */
	public static function getInboundInternalLinks( $linkedPostId, $limit = 20, $offset = 0 ) {
		$postTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
		$postStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
		if ( empty( $postTypes ) || empty( $postStatuses ) ) {
			return [];
		}

		$implodedPostTypes    = aioseo()->helpers->implodeWhereIn( $postTypes, true );
		$implodedPostStatuses = aioseo()->helpers->implodeWhereIn( $postStatuses, true );
		$links                = aioseo()->core->db->start( 'aioseo_links as al' )
			->select( 'al.*' )
			->join( 'posts as p', 'al.post_id = p.ID' )
			->whereRaw( "p.post_type IN ( $implodedPostTypes )" )
			->whereRaw( "p.post_status IN ( $implodedPostStatuses )" )
			->where( 'al.linked_post_id', $linkedPostId )
			->limit( $limit, $offset )
			->run()
			->models( 'AIOSEO\\Plugin\\Addon\\LinkAssistant\\Models\\Link' );

		foreach ( $links as $link ) {
			$link->context            = new \stdClass;
			$link->context->permalink = get_permalink( $link->post_id );
			$link->context->postTitle = aioseoLinkAssistant()->helpers->getPostTitle( $link->post_id );
			$link->context->editLink  = get_edit_post_link( $link->post_id, '' );

			// Post Type data.
			$postTypeObject           = get_post_type_object( get_post_type( $link->post_id ) );
			$link->context->postType  = aioseo()->helpers->getPostType( $postTypeObject );
		}

		return $links;
	}

	/**
	 * Returns outbound internal links for the given post.
	 *
	 * @since 1.0.0
	 *
	 * @param  int         $postId The post ID.
	 * @param  int         $limit  The limit.
	 * @param  int         $offset The offset.
	 * @return array[Link]         The Links.
	 */
	public static function getOutboundInternalLinks( $postId, $limit = 20, $offset = 0 ) {
		$postTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
		$postStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
		if ( empty( $postTypes ) || empty( $postStatuses ) ) {
			return [];
		}

		$implodedPostTypes    = aioseo()->helpers->implodeWhereIn( $postTypes, true );
		$implodedPostStatuses = aioseo()->helpers->implodeWhereIn( $postStatuses, true );
		$links                = aioseo()->core->db->start( 'aioseo_links as al' )
			->select( 'al.*' )
			->join( 'posts as p', 'al.post_id = p.ID' )
			->whereRaw( "p.post_type IN ( $implodedPostTypes )" )
			->whereRaw( "p.post_status IN ( $implodedPostStatuses )" )
			->where( 'al.post_id', $postId )
			->where( 'al.internal', 1 )
			->limit( $limit, $offset )
			->run()
			->models( 'AIOSEO\\Plugin\\Addon\\LinkAssistant\\Models\\Link' );

		foreach ( $links as $link ) {
			if ( ! empty( $link->linked_post_id ) ) {
				$link->context            = new \stdClass;
				$link->context->postTitle = aioseoLinkAssistant()->helpers->getPostTitle( $link->linked_post_id );
				$link->context->editLink  = get_edit_post_link( $link->linked_post_id, '' );

				// Post Type data.
				$postTypeObject           = get_post_type_object( get_post_type( $link->linked_post_id ) );
				$link->context->postType  = aioseo()->helpers->getPostType( $postTypeObject );
			}
		}

		return $links;
	}

	/**
	 * Returns affiliate links for the given post.
	 *
	 * @since 1.0.0
	 *
	 * @param  int         $postId The post ID.
	 * @param  int         $limit  The limit.
	 * @param  int         $offset The offset.
	 * @return array[Link]         The Links.
	 */
	public static function getAffiliateLinks( $postId, $limit = 20, $offset = 0 ) {
		$postTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
		$postStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
		if ( empty( $postTypes ) || empty( $postStatuses ) ) {
			return [];
		}

		$implodedPostTypes    = aioseo()->helpers->implodeWhereIn( $postTypes, true );
		$implodedPostStatuses = aioseo()->helpers->implodeWhereIn( $postStatuses, true );
		$links                = aioseo()->core->db->start( 'aioseo_links as al' )
			->select( 'al.*' )
			->join( 'posts as p', 'al.post_id = p.ID' )
			->whereRaw( "p.post_type IN ( $implodedPostTypes )" )
			->whereRaw( "p.post_status IN ( $implodedPostStatuses )" )
			->where( 'al.post_id', $postId )
			->where( 'al.affiliate', 1 )
			->limit( $limit, $offset )
			->run()
			->models( 'AIOSEO\\Plugin\\Addon\\LinkAssistant\\Models\\Link' );

		return $links;
	}

	/**
	 * Returns external links for the given post.
	 *
	 * @since 1.0.0
	 *
	 * @param  int         $postId The post ID.
	 * @param  int         $limit  The limit.
	 * @param  int         $offset The offset.
	 * @return array[Link]         The Links.
	 */
	public static function getExternalLinks( $postId, $limit = 20, $offset = 0 ) {
		$postTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
		$postStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
		if ( empty( $postTypes ) || empty( $postStatuses ) ) {
			return [];
		}

		$implodedPostTypes    = aioseo()->helpers->implodeWhereIn( $postTypes, true );
		$implodedPostStatuses = aioseo()->helpers->implodeWhereIn( $postStatuses, true );
		$links                = aioseo()->core->db->start( 'aioseo_links as al' )
			->select( 'al.*' )
			->join( 'posts as p', 'al.post_id = p.ID' )
			->whereRaw( "p.post_type IN ( $implodedPostTypes )" )
			->whereRaw( "p.post_status IN ( $implodedPostStatuses )" )
			->where( 'al.post_id', $postId )
			->where( 'al.external', 1 )
			->limit( $limit, $offset )
			->run()
			->models( 'AIOSEO\\Plugin\\Addon\\LinkAssistant\\Models\\Link' );

		return $links;
	}

	/**
	 * Returns the total of inbound internal links that refer to the given post.
	 *
	 * @since 1.0.0
	 *
	 * @param  int $linkedPostId The ID of the post the Link refers to.
	 * @return int               The total.
	 */
	public static function getTotalInboundInternalLinks( $linkedPostId ) {
		$postTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
		$postStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
		if ( empty( $postTypes ) || empty( $postStatuses ) ) {
			return 0;
		}

		$implodedPostTypes    = aioseo()->helpers->implodeWhereIn( $postTypes, true );
		$implodedPostStatuses = aioseo()->helpers->implodeWhereIn( $postStatuses, true );
		$links                = aioseo()->core->db->start( 'aioseo_links as al' )
			->select( 'al.*' )
			->join( 'posts as p', 'al.post_id = p.ID' )
			->whereRaw( "p.post_type IN ( $implodedPostTypes )" )
			->whereRaw( "p.post_status IN ( $implodedPostStatuses )" )
			->where( 'al.linked_post_id', $linkedPostId )
			->count();

		return $links;
	}

	/**
	 * Returns the total of outbound internal links for the given post.
	 *
	 * @since 1.0.0
	 *
	 * @param  int $postId The post ID.
	 * @return int         The total.
	 */
	public static function getTotalOutboundInternalLinks( $postId ) {
		$postTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
		$postStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
		if ( empty( $postTypes ) || empty( $postStatuses ) ) {
			return 0;
		}

		$implodedPostTypes    = aioseo()->helpers->implodeWhereIn( $postTypes, true );
		$implodedPostStatuses = aioseo()->helpers->implodeWhereIn( $postStatuses, true );
		$links                = aioseo()->core->db->start( 'aioseo_links as al' )
			->select( 'al.*' )
			->join( 'posts as p', 'al.post_id = p.ID' )
			->whereRaw( "p.post_type IN ( $implodedPostTypes )" )
			->whereRaw( "p.post_status IN ( $implodedPostStatuses )" )
			->where( 'al.post_id', $postId )
			->where( 'al.internal', 1 )
			->count();

		return $links;
	}

	/**
	 * Returns the total of affiliate links for the given post.
	 *
	 * @since 1.0.0
	 *
	 * @param  int $postId The post ID.
	 * @return int         The total.
	 */
	public static function getTotalAffiliateLinks( $postId ) {
		$postTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
		$postStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
		if ( empty( $postTypes ) || empty( $postStatuses ) ) {
			return 0;
		}

		$implodedPostTypes    = aioseo()->helpers->implodeWhereIn( $postTypes, true );
		$implodedPostStatuses = aioseo()->helpers->implodeWhereIn( $postStatuses, true );
		$links                = aioseo()->core->db->start( 'aioseo_links as al' )
			->select( 'al.*' )
			->join( 'posts as p', 'al.post_id = p.ID' )
			->whereRaw( "p.post_type IN ( $implodedPostTypes )" )
			->whereRaw( "p.post_status IN ( $implodedPostStatuses )" )
			->where( 'al.post_id', $postId )
			->where( 'al.affiliate', 1 )
			->count();

		return $links;
	}

	/**
	 * Returns the total of external links for the given post.
	 *
	 * @since 1.0.0
	 *
	 * @param  int $postId The post ID.
	 * @return int         The total.
	 */
	public static function getTotalExternalLinks( $postId ) {
		$postTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
		$postStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
		if ( empty( $postTypes ) || empty( $postStatuses ) ) {
			return 0;
		}

		$implodedPostTypes    = aioseo()->helpers->implodeWhereIn( $postTypes, true );
		$implodedPostStatuses = aioseo()->helpers->implodeWhereIn( $postStatuses, true );
		$links                = aioseo()->core->db->start( 'aioseo_links as al' )
			->select( 'al.*' )
			->join( 'posts as p', 'al.post_id = p.ID' )
			->whereRaw( "p.post_type IN ( $implodedPostTypes )" )
			->whereRaw( "p.post_status IN ( $implodedPostStatuses )" )
			->where( 'al.post_id', $postId )
			->where( 'al.external', 1 )
			->count();

		return $links;
	}

	/**
	 * Deletes all Links for the given post.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $linkId The Link ID.
	 * @return void
	 */
	public static function deleteLinks( $postId ) {
		aioseo()->core->db->delete( 'aioseo_links' )
			->where( 'post_id', $postId )
			->run();
	}

	/**
	 * Deletes the Link with the given ID.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $linkId The Link ID.
	 * @return void
	 */
	public static function deleteLinkById( $linkId ) {
		aioseo()->core->db->delete( 'aioseo_links' )
			->where( 'id', $linkId )
			->run();
	}

	/**
	 * Sanitizes the link object.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $link The link data.
	 * @return array       The sanitized link data.
	 */
	public static function sanitizeLink( $link ) {
		$nullFields    = [ 'linked_post_id' ];
		$booleanFields = [
			'internal',
			'affiliate',
			'external'
		];

		$sanitizedLink = [];
		foreach ( $link as $k => $v ) {
			switch ( $k ) {
				case 'post_id':
				case 'linked_post_id':
					if ( null === $v && in_array( $k, $nullFields, true ) ) {
						break;
					}
					$v = intval( $v );
					break;
				case 'internal':
				case 'external':
				case 'affiliate':
					$v = rest_sanitize_boolean( $v );
					break;
				case 'url':
					$v = esc_url( $v );
					break;
				case 'hostname':
				case 'anchor':
				case 'phrase':
				case 'paragraph':
					$v = sanitize_text_field( $v );
					break;
				case 'phrase_html':
				case 'paragraph_html':
					$v = aioseoLinkAssistant()->helpers->wpKsesPhrase( $v );
					break;
				default:
					break;
			}

			if (
				empty( $v ) &&
				! in_array( $k, $booleanFields, true ) &&
				! in_array( $k, $nullFields, true )
			) {
				return [];
			}

			$sanitizedLink[ $k ] = esc_sql( $v );
		}

		return $sanitizedLink;
	}

	/**
	 * Checks whether the given link object is a valid one in the context of Link Assistant.
	 * There are a number of reasons why a link might be invalid, such as empty props or because the link is wrapped around HTML elements like an image but actually doesn't contain text.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $link The link data.
	 * @return bool        Whether the link is valid or not.
	 */
	public static function validateLink( $link ) {
		$propsToCheck = [
			'url',
			'hostname',
			'anchor',
			'phrase',
			'phrase_html',
			'paragraph',
			'paragraph_html'
		];

		foreach ( $propsToCheck as $prop ) {
			$value = wp_strip_all_tags( $link[ $prop ] );
			if ( empty( $value ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Returns posts with their links for the Links Report.
	 *
	 * @since 1.0.0
	 *
	 * @param  int        $limit       The limit.
	 * @param  int        $offset      The offset.
	 * @param  string     $whereClause An optional WHERE clause for search queries.
	 * @param  string     $filter      An optional filter for the results.
	 * @param  int|string $termId      An optional ID or "all" for term(s) the results need to be assigned to.
	 * @return array                   The posts with their links.
	 */
	public static function getPosts( $limit = 20, $offset = 0, $whereClause = '', $filter = '', $termId = 0 ) {
		$includedPostTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
		$includedPostStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
		if ( empty( $includedPostTypes ) || empty( $includedPostStatuses ) ) {
			return [];
		}

		$query = self::getPostsBaseQuery( $filter, $termId );

		if ( ! empty( $whereClause ) ) {
			$query = $query->whereRaw( $whereClause );
		}

		$query = $query->orderBy( 'p.post_date DESC' )
			->limit( $limit, $offset );

		$posts = $query->run()->result();

		if ( empty( $posts ) ) {
			return [];
		}

		foreach ( $posts as $post ) {
			if ( ! $post->post_title ) {
				$post->post_title = __( '(no title)', 'default' );
			}

			$post->links              = aioseoLinkAssistant()->helpers->getPostLinks( $post->ID, 5, 0 );
			$post->context            = new \stdClass;
			$post->context->postTitle = aioseoLinkAssistant()->helpers->getPostTitle( $post->ID );
			$post->context->permalink = get_permalink( $post->ID );
			$post->context->editLink  = get_edit_post_link( $post->ID, '' );

			// Post Type data.
			$postTypeObject           = get_post_type_object( get_post_type( $post->ID ) );
			$post->context->postType  = aioseo()->helpers->getPostType( $postTypeObject );
		}

		return $posts;
	}

	/**
	 * Returns the total amount of posts for the Links Report.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $whereClause       An optional WHERE clause for search queries.
	 * @param  string $filter            An optional filter for the results.
	 * @param  array  $additionalFilters An optional array of additional filters.
	 * @return int                       The total amount of posts.
	 */
	public static function getTotalPosts( $whereClause = '', $filter = '', $additionalFilters = [] ) {
		$includedPostTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
		$includedPostStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
		if ( empty( $includedPostTypes ) || empty( $includedPostStatuses ) ) {
			return 0;
		}

		$query = self::getPostsBaseQuery( $filter, $additionalFilters );

		if ( ! empty( $whereClause ) ) {
			$query = $query->whereRaw( $whereClause );
		}

		return $query->count();
	}

	/**
	 * Returns the base query for the Links Report posts.
	 *
	 * @since 1.0.0
	 *
	 * @param  string   $filter            An optional filter for the results.
	 * @param  array    $additionalFilters An optional array of filters to use.
	 * @return Database                    The query object.
	 */
	private static function getPostsBaseQuery( $filter, $additionalFilters = [] ) {
		$includedPostTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
		$includedPostStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
		$excludedPostIds      = aioseoLinkAssistant()->helpers->getExcludedPostIds();

		$query = aioseo()->core->db->start( 'posts as p' )
			->select( 'p.ID, p.post_title, p.post_date, p.post_status' )
			->join( 'aioseo_posts as ap', 'p.ID = ap.post_id' )
			->whereRaw( 'ap.link_scan_date IS NOT NULL' );

		if ( ! empty( $includedPostStatuses ) ) {
			$includedPostStatuses = aioseo()->helpers->implodeWhereIn( $includedPostStatuses, true );
			$query->whereRaw( "p.post_status IN ( $includedPostStatuses )" );
		}

		if ( ! empty( $includedPostTypes ) ) {
			$includedPostTypes = aioseo()->helpers->implodeWhereIn( $includedPostTypes, true );
			$query->whereRaw( "p.post_type IN ( $includedPostTypes )" );
		}

		if ( ! empty( $excludedPostIds ) ) {
			$excludedPostIds = aioseo()->helpers->implodeWhereIn( $excludedPostIds, true );
			$query->whereRaw( "p.ID NOT IN ( $excludedPostIds )" );
		}

		if ( ! empty( $filter ) ) {
			$prefix = aioseo()->core->db->prefix;

			switch ( $filter ) {
				case 'linking-opportunities':
					$tableName = $prefix . 'aioseo_links_suggestions';
					$query = $query->whereRaw( "p.ID IN (
						SELECT als.post_id FROM $tableName as als
						WHERE dismissed = 0
						GROUP BY als.post_id
					)" );
					break;
				case 'orphaned-posts':
					$tableName = $prefix . 'aioseo_links';
					$query = $query->whereRaw( "p.ID IN (
						SELECT al.linked_post_id FROM $tableName as al
						WHERE al.internal = 1
						GROUP BY al.linked_post_id
					)" );
					break;
				default:
					break;
			}
		}

		if ( ! empty( $additionalFilters ) ) {
			if ( ! empty( $additionalFilters['post-type'] ) ) {
				$postTypes = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
				if ( in_array( $additionalFilters['post-type'], $postTypes, true ) ) {
					$query = $query->where( 'p.post_type', $additionalFilters['post-type'] );
				}
			}

			if ( ! empty( $additionalFilters['term'] ) ) {
				if ( 'all' === $additionalFilters['term'] ) {
					$taxonomy = aioseoLinkAssistant()->helpers->getFirstTaxonomy( $filter );
					if ( ! empty( $taxonomy ) ) {
						$query = $query->join( 'term_relationships as tr', 'p.ID = tr.object_id' )
							->join( 'term_taxonomy as tt', 'tr.term_taxonomy_id = tt.term_id' )
							->where( 'tt.taxonomy', $taxonomy->name );
					}
				} else {
					$query = $query->join( 'term_relationships as tr', 'p.ID = tr.object_id' )
						->where( 'tr.term_taxonomy_id', $additionalFilters['term'] );
				}
			}
		}

		return $query;
	}

	/**
	 * Returns the total amount of links on the site.
	 *
	 * @since 1.0.0
	 *
	 * @return int The amount of links on the site.
	 */
	public static function getSiteTotalLinks() {
		static $links = null;
		if ( null === $links ) {
			$query = aioseo()->core->db->start( 'aioseo_links as al' );

			$postTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
			$postStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
			if ( empty( $postTypes ) || empty( $postStatuses ) ) {
				return 0;
			}

			$implodedPostTypes    = aioseo()->helpers->implodeWhereIn( $postTypes, true );
			$implodedPostStatuses = aioseo()->helpers->implodeWhereIn( $postStatuses, true );
			$query                = $query->join( 'posts as p', 'al.post_id = p.ID' )
				->whereRaw( "p.post_type IN ( $implodedPostTypes )" )
				->whereRaw( "p.post_status IN ( $implodedPostStatuses )" );

			$excludedPostIds = aioseoLinkAssistant()->helpers->getExcludedPostIds();
			if ( ! empty( $excludedPostIds ) ) {
				$implodedPostIds = aioseo()->helpers->implodeWhereIn( $excludedPostIds );
				$query = $query->whereRaw( "p.ID NOT IN ( $implodedPostIds )" );
			}

			$links = $query->count();
		}

		return $links;
	}

	/**
	 * Returns the total amount of external links on the site.
	 *
	 * @since 1.0.0
	 *
	 * @return int The amount of external links on the site.
	 */
	public static function getSiteTotalExternalLinks() {
		static $links = null;
		if ( null === $links ) {
			$query = aioseo()->core->db->start( 'aioseo_links as al' )->where( 'al.external', 1 );

			$postTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
			$postStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
			if ( empty( $postTypes ) || empty( $postStatuses ) ) {
				return 0;
			}

			$implodedPostTypes    = aioseo()->helpers->implodeWhereIn( $postTypes, true );
			$implodedPostStatuses = aioseo()->helpers->implodeWhereIn( $postStatuses, true );
			$query                = $query->join( 'posts as p', 'al.post_id = p.ID' )
				->whereRaw( "p.post_type IN ( $implodedPostTypes )" )
				->whereRaw( "p.post_status IN ( $implodedPostStatuses )" );

			$excludedPostIds = aioseoLinkAssistant()->helpers->getExcludedPostIds();
			if ( ! empty( $excludedPostIds ) ) {
				$implodedPostIds = aioseo()->helpers->implodeWhereIn( $excludedPostIds );
				$query = $query->whereRaw( "p.ID NOT IN ( $implodedPostIds )" );
			}

			$links = $query->count();
		}

		return $links;
	}

	/**
	 * Returns the total amount of affiliate links on the site.
	 *
	 * @since 1.0.0
	 *
	 * @return int The amount of affiliate links on the site.
	 */
	public static function getSiteTotalAffiliateLinks() {
		static $links = null;
		if ( null === $links ) {
			$query = aioseo()->core->db->start( 'aioseo_links as al' )->where( 'al.affiliate', 1 );

			$postTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
			$postStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
			if ( empty( $postTypes ) || empty( $postStatuses ) ) {
				return 0;
			}

			$implodedPostTypes    = aioseo()->helpers->implodeWhereIn( $postTypes, true );
			$implodedPostStatuses = aioseo()->helpers->implodeWhereIn( $postStatuses, true );
			$query                = $query->join( 'posts as p', 'al.post_id = p.ID' )
				->whereRaw( "p.post_type IN ( $implodedPostTypes )" )
				->whereRaw( "p.post_status IN ( $implodedPostStatuses )" );

			$excludedPostIds = aioseoLinkAssistant()->helpers->getExcludedPostIds();
			if ( ! empty( $excludedPostIds ) ) {
				$implodedPostIds = aioseo()->helpers->implodeWhereIn( $excludedPostIds );
				$query = $query->whereRaw( "p.ID NOT IN ( $implodedPostIds )" );
			}

			$links = $query->count();
		}

		return $links;
	}

	/**
	 * Returns the total amount of internal links on the site.
	 *
	 * @since 1.0.0
	 *
	 * @return int The amount of internal links on the site.
	 */
	public static function getSiteTotalInternalLinks() {
		static $links = null;
		if ( null === $links ) {
			$query = aioseo()->core->db->start( 'aioseo_links as al' )
				->where( 'al.affiliate', 0 )
				->where( 'al.external', 0 );

			$postTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
			$postStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
			if ( empty( $postTypes ) || empty( $postStatuses ) ) {
				return 0;
			}

			$implodedPostTypes    = aioseo()->helpers->implodeWhereIn( $postTypes, true );
			$implodedPostStatuses = aioseo()->helpers->implodeWhereIn( $postStatuses, true );
			$query                = $query->join( 'posts as p', 'al.post_id = p.ID' )
				->whereRaw( "p.post_type IN ( $implodedPostTypes )" )
				->whereRaw( "p.post_status IN ( $implodedPostStatuses )" );

			$excludedPostIds = aioseoLinkAssistant()->helpers->getExcludedPostIds();
			if ( ! empty( $excludedPostIds ) ) {
				$implodedPostIds = aioseo()->helpers->implodeWhereIn( $excludedPostIds );
				$query = $query->whereRaw( "p.ID NOT IN ( $implodedPostIds )" );
			}

			$links = $query->count();
		}

		return $links;
	}

	/**
	 * Returns the total amount of internal posts that have been linked to on the site.
	 *
	 * @since 1.0.0
	 *
	 * @return int The amount of internal links on the site.
	 */
	public static function getSiteTotalLinkedPosts() {
		static $links = null;
		if ( null === $links ) {
			$postTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
			$postStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
			if ( empty( $postTypes ) || empty( $postStatuses ) ) {
				$links = 0;

				return $links;
			}

			$implodedPostTypes    = aioseo()->helpers->implodeWhereIn( $postTypes, true );
			$implodedPostStatuses = aioseo()->helpers->implodeWhereIn( $postStatuses, true );

			$query = aioseo()->core->db->start( 'aioseo_links as al' )
				->where( 'al.affiliate', 0 )
				->where( 'al.external', 0 )
				->where( 'al.linked_post_id !=', 0 )
				->where( 'ap.link_scan_date IS NOT', null )
				->join( 'aioseo_posts as ap', 'ap.post_id = al.linked_post_id' )
				->join( 'posts as p', 'ap.post_id = p.ID' )
				->whereRaw( "p.post_type IN ( $implodedPostTypes )" )
				->whereRaw( "p.post_status IN ( $implodedPostStatuses )" )
				->groupBy( 'al.linked_post_id' );

			$excludedPostIds = aioseoLinkAssistant()->helpers->getExcludedPostIds();
			if ( ! empty( $excludedPostIds ) ) {
				$implodedPostIds = aioseo()->helpers->implodeWhereIn( $excludedPostIds );
				$query = $query->whereRaw( "p.ID NOT IN ( $implodedPostIds )" );
			}

			$links = $query->count();
		}

		return $links;
	}

	/**
	 * Returns links grouped per domain/hostname for the Domains Report.
	 *
	 * @since 1.0.0
	 *
	 * @param  int    $limit       The limit.
	 * @param  int    $offset      The offset.
	 * @param  string $whereClause An optional WHERE clause for search queries.
	 * @return array               The domains.
	 */
	public static function getDomains( $limit = 20, $offset = 0, $whereClause = null ) {
		$postTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
		$postStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
		if ( empty( $postTypes ) || empty( $postStatuses ) ) {
			return [];
		}

		// First, we get a list of hostnames with external links.
		$implodedPostTypes    = aioseo()->helpers->implodeWhereIn( $postTypes, true );
		$implodedPostStatuses = aioseo()->helpers->implodeWhereIn( $postStatuses, true );
		$query                = aioseo()->core->db->start( 'aioseo_links as al' )
			->select( 'al.hostname as hostname, count( al.hostname ) as count' )
			->join( 'posts as p', 'al.post_id = p.ID' )
			->whereRaw( "p.post_type IN ( $implodedPostTypes )" )
			->whereRaw( "p.post_status IN ( $implodedPostStatuses )" )
			->where( 'al.external', 1 );

		if ( $whereClause ) {
			$query = $query->whereRaw( $whereClause );
		}

		$hostnames = $query->groupBy( 'hostname' )
			->orderBy( 'count DESC, hostname ASC' )
			->limit( $limit, $offset )
			->run()
			->result();

		$hostnames = array_map( function( $hostname ) {
			return $hostname->hostname;
		}, $hostnames );

		// Then, we get posts with links to the relevant hostname for each of the hostnames.
		$domainsWithPosts = [];
		foreach ( $hostnames as $hostname ) {
			$domainPosts      = [ $hostname => self::getDomainPostLinks( $hostname ) ];
			$domainsWithPosts = array_merge( $domainsWithPosts, $domainPosts );
		}

		// The WpTable component requires an array so we can't have keys on the first level and need the object into an array.
		$usedDomains    = [];
		$domainsAsArray = [];
		foreach ( $domainsWithPosts as $domain => $posts ) {
			if ( ! in_array( $domain, $usedDomains, true ) ) {
				$usedDomains[] = $domain;
			}

			$index                              = array_search( $domain, $usedDomains, true );
			$domainsAsArray[ $index ][ $domain ] = $posts;
		}

		// Finally, we need to re-sort the domains by the amount links they have.
		$sortedDomains = [];
		foreach ( $domainsAsArray as $wrapper ) {
			foreach ( $wrapper as $hostname => $posts ) {
				foreach ( $hostnames as $index => $hostname2 ) {
					if ( $hostname === $hostname2 ) {
						$sortedDomains[ $index ] = $wrapper;
					}
				}
			}
		}
		ksort( $sortedDomains );

		return $sortedDomains;
	}

	/**
	 * Returns the total amount of domains with links.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $whereClause An optional WHERE clause for search queries.
	 * @return int                 The total amount of domains.
	 */
	public static function getTotalDomains( $whereClause = null ) {
		$postTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
		$postStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
		if ( empty( $postTypes ) || empty( $postStatuses ) ) {
			return 0;
		}

		$implodedPostTypes    = aioseo()->helpers->implodeWhereIn( $postTypes, true );
		$implodedPostStatuses = aioseo()->helpers->implodeWhereIn( $postStatuses, true );
		$query                = aioseo()->core->db->start( 'aioseo_links as al' )
			->where( 'al.external', 1 )
			->join( 'posts as p', 'al.post_id = p.ID' )
			->whereRaw( "p.post_type IN ( $implodedPostTypes )" )
			->whereRaw( "p.post_status IN ( $implodedPostStatuses )" );

		if ( $whereClause ) {
			$query = $query->whereRaw( $whereClause );
		}

		return $query->groupBy( 'hostname' )
			->run()
			->numRows();
	}

	/**
	 * Returns posts that have links to the given hostname.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $hostname The hostname.
	 * @param  int    $limit    The limit.
	 * @param  int    $offset   The offset.
	 * @return array            The posts with their links.
	 */
	public static function getDomainPostLinks( $hostname, $limit = 5, $offset = 0 ) {
		$postTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
		$postStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
		if ( empty( $postTypes ) || empty( $postStatuses ) ) {
			return [];
		}

		// First, figure out which posts link to the current hostname.
		// We can't use the result of this as a subquery here because MySQL doesn't support LIMIT clauses in subqueries yet.
		$implodedPostTypes    = aioseo()->helpers->implodeWhereIn( $postTypes, true );
		$implodedPostStatuses = aioseo()->helpers->implodeWhereIn( $postStatuses, true );
		$postIds              = aioseo()->core->db->start( 'aioseo_links as al' )
			->select( 'al.post_id, count( al.post_id ) as count' )
			->join( 'posts as p', 'al.post_id = p.ID' )
			->whereRaw( "p.post_type IN ( $implodedPostTypes )" )
			->whereRaw( "p.post_status IN ( $implodedPostStatuses )" )
			->where( 'al.external', 1 )
			->where( 'al.hostname', $hostname )
			->groupBy( 'al.post_id' )
			->limit( $limit, $offset )
			->run()
			->result();

		$postIds = array_map( function( $postId ) {
			return $postId->post_id;
		}, $postIds );

		// Then, get all links for those posts.
		$implodedPostIds = aioseo()->helpers->implodeWhereIn( $postIds );
		$links = aioseo()->core->db->start( 'aioseo_links' )
			->where( 'external', 1 )
			->whereRaw( "post_id IN ( $implodedPostIds )" )
			->where( 'hostname', $hostname )
			->run()
			->result();

		$posts = [];
		foreach ( $links as $link ) {
			$posts[ $link->post_id ]['links'][] = $link;
		}

		// Now, we just need to add the context.

		$index            = 0;
		$postsWithContext = [];
		foreach ( $posts as $postId => $post ) {
			$postObject      = aioseo()->helpers->getPost( $postId );
			$postTypeObject  = get_post_type_object( get_post_type( $postId ) );
			$post['context'] = [
				'postTitle'   => aioseo()->helpers->decodeHtmlEntities( $postObject->post_title ),
				'publishDate' => $postObject->post_date,
				'permalink'   => get_permalink( $postId ),
				'editLink'    => get_edit_post_link( $postId, '' ),
				'postType'    => aioseo()->helpers->getPostType( $postTypeObject )
			];

			if ( 0 === $index ) {
				$totalPosts = self::getTotalDomainPosts( $hostname );
				$totalLinks = self::getTotalDomainLinks( $hostname );

				$post['totals'] = [
					'page'       => ( $offset + $limit ) / $limit,
					'pages'      => ceil( $totalPosts / $limit ),
					'total'      => $totalPosts,
					'totalLinks' => $totalLinks
				];
			}

			$postsWithContext[] = $post;
			$index++;
		}

		return $postsWithContext;
	}

	/**
	 * Returns the total amount of posts with external links refering to the given hostname/domain.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $hostname The hostname.
	 * @return int              The total amount of posts.
	 */
	public static function getTotalDomainPosts( $hostname ) {
		$postTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
		$postStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
		if ( empty( $postTypes ) || empty( $postStatuses ) ) {
			return 0;
		}

		$implodedPostTypes    = aioseo()->helpers->implodeWhereIn( $postTypes, true );
		$implodedPostStatuses = aioseo()->helpers->implodeWhereIn( $postStatuses, true );

		return aioseo()->core->db->start( 'aioseo_links as al' )
			->select( 'al.post_id, count( al.post_id ) as count' )
			->join( 'posts as p', 'al.post_id = p.ID' )
			->whereRaw( "p.post_type IN ( $implodedPostTypes )" )
			->whereRaw( "p.post_status IN ( $implodedPostStatuses )" )
			->where( 'al.external', 1 )
			->where( 'al.hostname', $hostname )
			->groupBy( 'al.post_id' )
			->count();
	}

	/**
	 * Returns all external links referring to the given hostname/domain.
	 *
	 * @since 1.0.0
	 *
	 * @param  string      $hostname The hostname.
	 * @return array[Link]           The Links.
	 */
	public static function getDomainLinks( $hostname ) {
		$postTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
		$postStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
		if ( empty( $postTypes ) || empty( $postStatuses ) ) {
			return [];
		}

		$implodedPostTypes    = aioseo()->helpers->implodeWhereIn( $postTypes, true );
		$implodedPostStatuses = aioseo()->helpers->implodeWhereIn( $postStatuses, true );

		return aioseo()->core->db->start( 'aioseo_links al' )
			->join( 'posts as p', 'al.post_id = p.ID' )
			->whereRaw( "p.post_type IN ( $implodedPostTypes )" )
			->whereRaw( "p.post_status IN ( $implodedPostStatuses )" )
			->where( 'al.hostname', $hostname )
			->where( 'al.external', 1 )
			->run()
			->models( 'AIOSEO\\Plugin\\Addon\\LinkAssistant\\Models\\Link' );
	}

	/**
	 * Returns the total amount of external links referring to the given hostname/domain.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $hostname The hostname.
	 * @return int              The total amount of links.
	 */
	public static function getTotalDomainLinks( $hostname ) {
		$postTypes    = aioseoLinkAssistant()->helpers->getIncludedPostTypes();
		$postStatuses = aioseoLinkAssistant()->helpers->getIncludedPostStatuses();
		if ( empty( $postTypes ) || empty( $postStatuses ) ) {
			return 0;
		}

		$implodedPostTypes    = aioseo()->helpers->implodeWhereIn( $postTypes, true );
		$implodedPostStatuses = aioseo()->helpers->implodeWhereIn( $postStatuses, true );

		return aioseo()->core->db->start( 'aioseo_links as al' )
			->join( 'posts as p', 'al.post_id = p.ID' )
			->whereRaw( "p.post_type IN ( $implodedPostTypes )" )
			->whereRaw( "p.post_status IN ( $implodedPostStatuses )" )
			->where( 'al.hostname', $hostname )
			->where( 'al.external', 1 )
			->count();
	}
}