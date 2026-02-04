/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	TextareaControl,
	SelectControl,
	Disabled,
} from '@wordpress/components';
import { CheckboxControl } from '@woocommerce/blocks-checkout';

/**
 * Edit component for SMS consent block
 */
export const Edit = ( { attributes, setAttributes } ) => {
	const { text, disclaimerText, smsStatus } = attributes;
	const blockProps = useBlockProps();

	const defaultText = __( 'Text me with news and offers', 'mailchimp-for-woocommerce' );
	const defaultDisclaimer = __( 'By providing your phone number, you agree to receive promotional and marketing messages...', 'mailchimp-for-woocommerce' );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'SMS Consent Settings', 'mailchimp-for-woocommerce' ) }>
					<TextControl
						label={ __( 'Checkbox Label', 'mailchimp-for-woocommerce' ) }
						value={ text }
						onChange={ ( value ) => setAttributes( { text: value } ) }
						placeholder={ defaultText }
					/>
					<TextareaControl
						label={ __( 'Disclaimer Text', 'mailchimp-for-woocommerce' ) }
						value={ disclaimerText }
						onChange={ ( value ) => setAttributes( { disclaimerText: value } ) }
						placeholder={ defaultDisclaimer }
					/>
					<SelectControl
						label={ __( 'Default Checkbox State', 'mailchimp-for-woocommerce' ) }
						value={ smsStatus }
						options={ [
							{ label: __( 'Checked by default', 'mailchimp-for-woocommerce' ), value: 'check' },
							{ label: __( 'Unchecked by default', 'mailchimp-for-woocommerce' ), value: 'uncheck' },
							{ label: __( 'Hidden', 'mailchimp-for-woocommerce' ), value: 'hide' },
						] }
						onChange={ ( value ) => setAttributes( { smsStatus: value } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<div className="wc-block-components-checkout-step__container mailchimp-sms-consent">
					<div className="wc-block-components-checkout-step__content">
						<CheckboxControl
							id="subscribe-to-sms-preview"
							checked={ smsStatus === 'check' }
							onChange={ () => {} }
						>
							<span>{ text || defaultText }</span>
						</CheckboxControl>
						{ smsStatus !== 'hide' && (
							<div style={{ marginTop: '12px', marginLeft: '28px' }}>
								<div style={{ 
									padding: '8px 12px', 
									border: '1px solid #ccc', 
									borderRadius: '4px',
									color: '#757575',
									fontSize: '14px'
								}}>
									{ __( 'SMS Phone Number', 'mailchimp-for-woocommerce' ) }
								</div>
								<p style={{ fontSize: '12px', color: '#666', marginTop: '8px' }}>
									{ disclaimerText || defaultDisclaimer }
								</p>
							</div>
						) }
					</div>
				</div>
			</Disabled>
		</div>
	);
};

export const Save = () => {
	return null;
};
