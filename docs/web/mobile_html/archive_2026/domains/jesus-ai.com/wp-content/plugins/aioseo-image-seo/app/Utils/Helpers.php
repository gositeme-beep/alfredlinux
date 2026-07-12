<?php
namespace AIOSEO\Plugin\Addon\ImageSeo\Utils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contains helper functions for the Image SEO addon.
 *
 * @since 1.1.0
 */
class Helpers {
	/**
	 * Strips punctuation from the given image attribute value.
	 *
	 * @since 1.1.0
	 *
	 * @param  string $string        The string.
	 * @param  string $attributeName The attribute name.
	 * @return string                The string value with punctuation replaced.
	 */
	public function stripPunctuation( $string, $attributeName ) {
		$charactersToConvert = $this->getCharactersToConvert( $attributeName );

		// We must first convert the specified characters to spaces.
		if ( ! empty( $charactersToConvert ) && 'filename' !== $attributeName ) {
			$pattern = implode( '|', $charactersToConvert );
			$string  = preg_replace( "/(${pattern})/i", ' ', $string );
		}

		// Then, strip the punctuation characters.
		$string = aioseo()->helpers->stripPunctuation( $string, $this->getCharactersToKeep( $attributeName ) );

		// Trim internal and external whitespace before returning.
		return preg_replace( '/[\s]+/u', ' ', trim( $string ) );
	}

	/**
	 * Returns the characters that shouldn't be stripped for a specific image attribute.
	 *
	 * @since 1.1.0
	 *
	 * @param  string $attributeName The image attribute name.
	 * @return array                 List of characters to keep.
	 */
	private function getCharactersToKeep( $attributeName ) {
		static $charactersToKeep = [];

		if ( isset( $charactersToKeep[ $attributeName ] ) ) {
			return $charactersToKeep[ $attributeName ];
		}

		$options = aioseo()->options->image->{$attributeName}->charactersToKeep->all();

		$mappedOptions = [
			'numbers'     => '\d',
			'apostrophe'  => "'",
			'ampersand'   => '&',
			'underscores' => '_',
			'plus'        => '+',
			'pound'       => '#',
			'dashes'      => '-',
		];

		foreach ( $options as $k => $enabled ) {
			if ( ! $enabled ) {
				unset( $mappedOptions[ $k ] );
			}
		}

		$charactersToKeep[ $attributeName ] = $mappedOptions;

		return $charactersToKeep[ $attributeName ];
	}

	/**
	 * Returns the characters that should be converted to spaces.
	 *
	 * @since 1.1.0
	 *
	 * @param  string $attributeName The image attribute name.
	 * @return array                 List of characters to convert to spaces.
	 */
	private function getCharactersToConvert( $attributeName ) {
		static $charactersToConvert = [
			'filename' => []
		];

		if ( isset( $charactersToConvert[ $attributeName ] ) ) {
			return $charactersToConvert[ $attributeName ];
		}

		if ( ! aioseo()->options->image->{$attributeName}->has( 'charactersToConvert' ) ) {
			return [];
		}

		$options = aioseo()->options->image->{$attributeName}->charactersToConvert->all();

		$mappedOptions = [
			'underscores' => '_',
			'dashes'      => '-',
		];

		foreach ( $options as $k => $enabled ) {
			if ( ! $enabled ) {
				unset( $mappedOptions[ $k ] );
			}
		}

		$charactersToConvert[ $attributeName ] = $mappedOptions;

		return $charactersToConvert[ $attributeName ];
	}
}