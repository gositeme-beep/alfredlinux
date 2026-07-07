<?php
namespace AIOSEO\Plugin\Addon\VideoSitemap\VideoSitemap;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles video URL extraction from other plugins/themes.
 *
 * @since 1.1.4
 */
class ThirdParty {
	/**
	 * The post object.
	 *
	 * @since 1.1.4
	 *
	 * @var WP_Post|Object
	 */
	private $post;

	/**
	 * Returns all video URLs for the given post that we can extract from other plugins/themes.
	 *
	 * @since 1.1.4
	 *
	 * @param  WP_Post|Object $post The post object.
	 * @return array                The video URLs.
	 */
	public function getVideoUrls( $post ) {
		$this->post = $post;

		$methodNames = [
			'elementor'
		];

		$videoUrls = [];
		foreach ( $methodNames as $methodName ) {
			$videoUrls = array_merge(
				$videoUrls,
				$this->{$methodName}()
			);
		}

		return array_unique( $videoUrls );
	}

	/**
	 * Returns a list of video URLs for Elementor.
	 * We support the regular Video component out-of-the-box but need custom support for the Video Playlist component.
	 *
	 * @since 1.1.4
	 *
	 * @return array The video URLs.
	 */
	private function elementor() {
		if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			return [];
		}

		// TODO: Once 4.1.7 is released, we can also call getPostPageBuilderName() to make sure the post is still actively using a page builder.
		$elementorData = get_post_meta( $this->post->ID, '_elementor_data', true );
		if ( empty( $elementorData ) ) {
			return [];
		}

		$elementorData = json_decode( $elementorData );
		if ( empty( $elementorData ) ) {
			return [];
		}

		$widgets = [];
		foreach ( $elementorData as $section ) {
			$widgets = array_merge(
				$widgets,
				$this->getWidgets( $section )
			);
		}

		$videoUrls = [];
		foreach ( $widgets as $widget ) {
			if ( ! isset( $widget->settings->tabs ) ) {
				continue;
			}

			foreach ( $widget->settings->tabs as $tab ) {
				if ( ! isset( $tab->type ) ) {
					$videoUrls[] = $tab->youtube_url;
					continue;
				}

				switch ( $tab->type ) {
					case 'vimeo':
						$videoUrls[] = $tab->vimeo_url;
						break;
					case 'hosted':
						$videoUrls[] = $tab->hosted_url->url;
						break;
					default:
						$videoUrls[] = $tab->youtube_url;
						break;
				}
			}
		}

		return $videoUrls;
	}

	/**
	 * Returns all widgets that are nested inside the given Elementor element.
	 *
	 * @since 1.1.4
	 *
	 * @param  Object $element The Elementor object.
	 * @return array           The nested widgets.
	 */
	private function getWidgets( $element ) {
		if ( ! isset( $element->elements ) ) {
			return [];
		}

		$widgets = [];

		// Use recursion to grab all nested widgets that are grandchildren (or even deeper).
		foreach ( $element->elements as $childElement ) {
			// Grab all video playlist widgets that are children of the current element.
			if ( 'widget' === $childElement->elType && 'video-playlist' === $childElement->widgetType ) {
				$widgets[] = $childElement;
			}

			// Use recursion to grab all nested widgets that are grandchildren (or even deeper).
			$widgets = array_merge(
				$widgets,
				$this->getWidgets( $childElement )
			);
		}

		return $widgets;
	}
}