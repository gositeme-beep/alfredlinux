<?php
namespace AIOSEO\Plugin\Addon\LocalBusiness\Blocks;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Map block class.
 *
 * @since 1.1.3
 */
class Map {
	/**
	 * Class constructor.
	 *
	 * @since 1.1.3
	 */
	public function __construct() {
		aioseo()->blocks->registerBlock(
			'aioseo/locationmap', [
				'attributes'      => [
					'locationId'   => [
						'type'    => 'number',
						'default' => null
					],
					'showLabel'    => [
						'type'    => 'boolean',
						'default' => true
					],
					'showIcon'     => [
						'type'    => 'boolean',
						'default' => true
					],
					'customMarker' => [
						'type'    => 'string',
						'default' => null
					],
					'width'        => [
						'type'    => 'string',
						'default' => '100%'
					],
					'height'       => [
						'type'    => 'string',
						'default' => '450px'
					],
					'label'        => [
						'type'    => 'string',
						'default' => __( 'Our location:', 'aioseo-local-business' ),
					],
					'dataObject'   => [
						'type'    => 'string',
						'default' => null
					],
					'updated'      => [
						'type'    => 'string',
						'default' => time()
					]
				],
				'render_callback' => [ $this, 'render' ],
				'editor_style'    => aioseoLocalBusiness()->assets->cssHandle( 'location-map.css' )
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @since 1.1.3
	 *
	 * @param  array  $blockAttributes The block attributes.
	 * @return string                  The output from the output buffering.
	 */
	public function render( $blockAttributes ) {
		$locationId = ! empty( $blockAttributes['locationId'] ) ? $blockAttributes['locationId'] : '';

		if ( $locationId ) {
			$location = aioseoLocalBusiness()->locations->getLocation( $locationId );
			if ( ! $location ) {
				return sprintf(
					__( 'Please fill in your Business Info for this %s.', 'aioseo-local-business' ),
					aioseoLocalBusiness()->postType->getSingleLabel()
				);
			}
		}

		ob_start();

		echo esc_html( aioseoLocalBusiness()->locations->outputLocationMap( $locationId, $blockAttributes ) );

		return ob_get_clean();
	}
}