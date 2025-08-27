<?php

/**
 * Accessibility Attributes for Gutenberg Blocks
 * 
 * @link							https://github.com/cheyennecenk/accessibility-attributes
 * @since						 	1.0.0
 * @package						accessibility-attributes
 * 
 * @wordpress-plugin
 * Plugin Name:       Accessibility Attributes for Gutenberg Blocks
 * Plugin URI:				https://github.com/cheyennecenk/accessibility-attributes
 * Description:       Block extension that adds attributes to core Gutenberg blocks for accessibility.
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Version:           1.0.0
 * Author:            Cheyenne Cenk
 * Author URI:        https://github.com/cheyennecenk
 * Text Domain:       accessibility-attributes
 *
 * @package           create-block
 */

namespace accessibility_attributes;

use WP_HTML_Tag_Processor;

/**
 * Enqueue specific modifications for the block editor.
 *
 * @return void
 */
function wpdev_enqueue_editor_modifications() {
	$asset_file = include plugin_dir_path( __FILE__ ) . 'build/index.asset.php';
	wp_enqueue_script( 'accessibility-attributes', plugin_dir_url( __FILE__ ) . 'build/index.js', $asset_file['dependencies'], $asset_file['version'], true );
}
add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\wpdev_enqueue_editor_modifications' );

/**
 * Apply aria-label + optional custom attributes to ALL blocks.
 * - For core/button: prefer the inner <a>. Fallback to first tag if no anchor.
 * - For other blocks: apply to the first tag in the rendered HTML.
 *
 * @param string $block_content
 * @param array  $block
 * @return string
 */
function filter_all_blocks_render( $block_content, $block ) {

	$attrs  = isset( $block['attrs'] ) ? $block['attrs'] : array();
	$label  = isset( $attrs['ariaLabel'] ) ? (string) $attrs['ariaLabel'] : '';
	$enable = ! empty( $attrs['enableCustomAttributes'] );
	$custom = isset( $attrs['customAttributes'] ) ? (string) $attrs['customAttributes'] : '';

	// Nothing to do?
	if ( $label === '' && ( ! $enable || $custom === '' ) ) {
		return $block_content;
	}

	$processor = new \WP_HTML_Tag_Processor( $block_content );

	$targeted = false;

	// Special-case core/button: set attributes on the anchor if present.
	if ( isset( $block['blockName'] ) && $block['blockName'] === 'core/button' ) {
		while ( $processor->next_tag() ) {
			if ( strtolower( $processor->get_tag() ) === 'a' ) {
				apply_attributes_to_processor( $processor, $label, $enable, $custom );
				$targeted = true;
				break;
			}
		}
	}

	// For all other blocks (or if <a> not found in button), apply to the first tag.
	if ( ! $targeted ) {
		$processor = new \WP_HTML_Tag_Processor( $block_content ); // restart to first tag
		if ( $processor->next_tag() ) {
			apply_attributes_to_processor( $processor, $label, $enable, $custom );
			$targeted = true;
		}
	}

	return $targeted ? $processor->get_updated_html() : $block_content;
}

/**
 * Helper: set aria-label and any custom attributes on the current tag.
 *
 * @param \WP_HTML_Tag_Processor $p
 * @param string $label
 * @param bool   $enable
 * @param string $custom
 * @return void
 */
function apply_attributes_to_processor( \WP_HTML_Tag_Processor $p, $label, $enable, $custom ) {
	// aria-label first (if provided)
	if ( $label !== '' ) {
		$p->set_attribute( 'aria-label', $label );
	}

	// Custom "name|value,name2|value2"
	if ( $enable && $custom !== '' ) {
		$pairs = array_map( 'trim', explode( ',', $custom ) );
		foreach ( $pairs as $pair ) {
			if ( $pair === '' ) {
				continue;
			}
			$bits = array_map( 'trim', explode( '|', $pair, 2 ) );
			if ( count( $bits ) !== 2 ) {
				continue;
			}
			list( $name, $value ) = $bits;

			// Basic validation for attribute names.
			if ( $name === '' || preg_match( '/[^a-zA-Z0-9:_\.\-]/', $name ) ) {
				continue;
			}

			// Avoid dangerous/structural attributes
			if ( in_array( strtolower( $name ), array( 'href', 'src', 'onclick', 'onload' ), true ) ) {
				continue;
			}

			$p->set_attribute( $name, $value );
		}
	}
}

add_filter( 'render_block', __NAMESPACE__ . '\filter_all_blocks_render', 10, 2 );