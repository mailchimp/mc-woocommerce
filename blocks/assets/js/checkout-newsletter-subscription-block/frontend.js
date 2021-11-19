/**
 * External dependencies
 */
import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';
import { withFilteredAttributes } from '@woocommerce/shared-hocs';
/**
 * Internal dependencies
 */
import Block from './block';
import attributes from './attributes';
import metadata from './block.json';

registerCheckoutBlock( {
	metadata,
	component: withFilteredAttributes( attributes )( Block ),
} );
