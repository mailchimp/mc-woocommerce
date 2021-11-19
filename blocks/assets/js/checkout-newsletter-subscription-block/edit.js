/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { CheckboxControl } from '@woocommerce/blocks-checkout';

/**
 * Internal dependencies
 */
import './style.scss';

export const Edit = ( { attributes, setAttributes } ) => {
	const { text, gdprHeadline } = attributes;
	const blockProps = useBlockProps();
	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Block options', 'mailchimp-for-woocommerce' ) }>
					Options for the block go here.
				</PanelBody>
			</InspectorControls>
			<div style={{display: 'flex'}}>
				<CheckboxControl
					id="newsletter-text"
					checked={ false }
					disabled={ true }
				/>
				<RichText
					value={ text }
					help={__( 'Set the newsletter confirmation text.', 'mailchimp-for-woocommerce' )}
					onChange={ ( value ) => setAttributes( { text: value } ) }
				/>
			</div>
			<RichText
				value={ gdprHeadline }
				help={__( 'Set the GDPR headline.', 'mailchimp-for-woocommerce' )}
				onChange={ ( value ) => setAttributes( { gdprHeadline: value } ) }
			/>
		</div>
	);
};

export const Save = () => {
	return <div { ...useBlockProps.save() } />;
};
