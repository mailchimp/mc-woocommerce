/**
 * External dependencies
 */
import { SVG, Path } from '@wordpress/components';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import metadata from './block.json';
import attributes from './attributes';

/**
 * SMS icon
 */
const smsIcon = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
		<Path 
			d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.17L4 17.17V4h16v12zM7 9h2v2H7zm4 0h2v2h-2zm4 0h2v2h-2z"
			fill="currentColor"
		/>
	</SVG>
);

/**
 * Register block
 */
registerBlockType( metadata, {
	icon: {
		src: smsIcon,
		foreground: '#874FB9',
	},
	edit: Edit,
	save: Save,
	attributes: {
		...metadata.attributes,
		...attributes,
	},
} );
