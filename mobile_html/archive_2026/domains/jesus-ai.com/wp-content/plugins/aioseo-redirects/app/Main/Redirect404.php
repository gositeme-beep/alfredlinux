<?php
namespace AIOSEO\Plugin\Addon\Redirects\Main;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Addon\Redirects\Utils;
use AIOSEO\Plugin\Addon\Redirects\Models;

/**
 * Main class to run our 404 redirects.
 *
 * @since 1.2.2
 */
class Redirect404 {
	/**
	 * Class constructor.
	 *
	 * @since 1.2.2
	 */
	public function __construct() {
		if ( ! aioseoRedirects()->options->advanced404s->enabled ) {
			return;
		}

		// Maybe redirect 404s.
		add_action( 'template_redirect', [ $this, 'templateRedirect' ], 2000 );

		// Maybe short-circuit WP's redirect guess.
		add_filter( 'pre_redirect_guess_404_permalink', [ $this, 'redirectGuess' ] );
	}

	/**
	 * Short-circuit WP's redirect guess.
	 *
	 * @since 1.2.2
	 *
	 * @param  string|null $guessUrl The pre guess url.
	 * @return void
	 */
	public function redirectGuess( $guessUrl ) {
		if ( ! Utils\Request::isRedirectTest() ) {
			$this->maybeRedirect();
		}

		return $guessUrl;
	}

	/**
	 * Intercept the request and redirect after logs have been saved.
	 *
	 * @since 1.2.2
	 *
	 * @return void
	 */
	public function templateRedirect() {
		// Skip redirecting if it's a redirect test.
		if ( ! is_404() || Utils\Request::isRedirectTest() ) {
			return;
		}

		$this->maybeRedirect();
	}

	/**
	 * Tries to redirect a 404.
	 *
	 * @since 1.2.2
	 *
	 * @return void
	 */
	public function maybeRedirect() {
		// Smart slug redirect.
		if ( aioseoRedirects()->options->advanced404s->redirectToSmart ) {
			$this->smartRedirect();
		}

		// We'll try redirecting to the parent first.
		if ( aioseoRedirects()->options->advanced404s->redirectToParent ) {
			$this->parentRedirect();
		}

		if ( aioseoRedirects()->options->advanced404s->redirectDefaultEnabled ) {
			switch ( aioseoRedirects()->options->advanced404s->redirectDefault ) {
				// Try redirecting to a Custom URL
				case 'custom':
					$this->customRedirect();
					break;
				// Last fallback is to the home page.
				case 'home':
					aioseoRedirects()->helpers->do404Redirect( get_home_url(), 'HOME' );
					break;
			}
		}
	}

	/**
	 * Smart redirect.
	 *
	 * @since 1.2.3
	 *
	 * @return void
	 */
	private function smartRedirect() {
		// Separate the path.
		$path = array_filter( explode( '/', Utils\Request::getRequestUrl() ) );

		// Get the last item which should be always the post name/slug.
		$slug = array_pop( $path );

		if ( empty( $slug ) ) {
			return;
		}

		// Look in the database if there's any post_type with the exact same name/slug.
		$post = aioseo()->db->start( 'posts' )
			->select( 'ID, post_type' )
			->where( [
				'post_name'   => $slug,
				'post_status' => 'publish'
			] )
			->run()
			->result();

		if (
			empty( $post ) ||
			empty( $post[0]->ID ) ||
			! aioseo()->helpers->isPostTypePublic( $post[0]->post_type )
		) {
			return;
		}

		$permalink = get_permalink( $post[0]->ID );
		if ( empty( $permalink ) ) {
			return;
		}

		// Let's redirect to the found post URL.
		aioseoRedirects()->helpers->do404Redirect( $permalink, 'SMART' );
		die;
	}

	/**
	 * Tries to redirect to a custom url.
	 *
	 * @since 1.2.3
	 *
	 * @return void
	 */
	private function customRedirect() {
		$url = aioseoRedirects()->options->advanced404s->redirectToCustomUrl;
		if ( empty( $url ) ) {
			return;
		}

		$url = Utils\Request::formatTargetUrl( $url );

		// Prevent redirect loop.
		if ( Utils\Request::formatTargetUrl( Utils\Request::getRequestUrl() ) === $url ) {
			return;
		}

		aioseoRedirects()->helpers->do404Redirect( $url, 'CUSTOM' );
	}

	/**
	 * Tries to redirect to a stored parent reference.
	 *
	 * @since 1.2.2
	 *
	 * @return void
	 */
	private function parentRedirect() {
		if ( ! aioseo()->license->hasAddonFeature( 'aioseo-redirects', '404-parent-redirect' ) ) {
			return;
		}

		$requestUrl  = Utils\Request::getRequestUrl();
		$redirect404 = Models\Redirect404::getRedirectByUrl( $requestUrl );
		if ( ! $redirect404->exists() ) {
			return;
		}

		$redirectParent = new RedirectParent404( $redirect404 );

		// Redirects below are in order of importance.
		// 1. First the parent post.
		$redirectParent->postParentRedirect();

		// 2. Second the parent term.
		$redirectParent->termParentRedirect();

		// 3. WooCommerce redirect to the shop page.
		$redirectParent->woocommerceParentRedirect();

		// 4. Lastly, let's try the post type archive.
		$redirectParent->postTypeArchiveRedirect();
	}
}