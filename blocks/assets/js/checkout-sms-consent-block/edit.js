/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody, RadioControl } from '@wordpress/components';
import { CheckboxControl  } from '@woocommerce/blocks-checkout';


/**
 * Internal dependencies
 */
import './style.scss';


export const Edit = ( { attributes, setAttributes } ) => {
	const { text, gdprStatus, checkboxSettings} = attributes;
	const blockProps = useBlockProps();
	const checked = gdprStatus === 'check';

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Block options', 'mailchimp-for-woocommerce' ) }>
					<p>{ __('Choose how you want the opt-in to your newsletter checkbox to render at checkout', 'mailchimp-for-woocommerce') }</p>
					<RadioControl
						selected={ gdprStatus }
						options={ checkboxSettings }
						onChange={ ( value ) => setAttributes( {gdprStatus: value } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<div style={{display: 'flex', lineHeight: '1.5em', alignItems: 'center'}}>
				<CheckboxControl
					id="newsletter-text"
					checked={ checked }
					disabled={ true }
					style={{marginTop: 0}}
				/>
				<RichText
					value={ text }
					help={__( 'Set the newsletter confirmation text.', 'mailchimp-for-woocommerce' )}
					onChange={ ( value ) => setAttributes( { text: value } ) }
				/>
			</div>
		</div>
	);
}

// not sure
export const Save = () => {
	return <div { ...useBlockProps.save() } />;
};
