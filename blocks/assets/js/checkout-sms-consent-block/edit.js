/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody, Disabled } from '@wordpress/components';
import { CheckboxControl  } from '@woocommerce/blocks-checkout';


/**
 * Internal dependencies
 */
import './style.scss';
import {useState} from "@wordpress/element";


export const Edit = ( { attributes, setAttributes } ) => {
	const { text, defaultDisclaimer, smsEnabled, usingSmsConsent} = attributes;
	const blockProps = useBlockProps();
	const [ smsSettingsText, setSmsSettingsText ] = useState( usingSmsConsent ? 'SMS is enabled' : 'SMS is disabled' );

	if (!smsEnabled) return '';

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Block options', 'mailchimp-for-woocommerce' ) }>
					<p>{ __('Enable or disable SMS marketing on checkout.', 'mailchimp-for-woocommerce') }</p>
					<CheckboxControl
						id="sms-settings-text"
						checked={ usingSmsConsent }
						onChange={ ( value ) => {
							setSmsSettingsText( value ? 'SMS is enabled' : 'SMS is disabled' );
							setAttributes( {usingSmsConsent: value } )
						}}
					>
						<span>{ smsSettingsText }</span>
					</CheckboxControl>
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<div style={{ marginTop: '12px', opacity: usingSmsConsent ? '1' : '.25' }} className="wc-block-components-checkout-step__container mailchimp-sms-consent">
					<div className="wc-block-components-checkout-step__content">
						<CheckboxControl
							id="newsletter-text"
							checked={ false }
							disabled={ true }
							style={{marginTop: 0}}
						>
							<span>{ usingSmsConsent ? text : 'SMS is currently disabled (only admins can see this)' }</span>
						</CheckboxControl>
						<div style={{ marginTop: '12px', display: usingSmsConsent ? 'inherit' : 'none' }}>
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
								{ defaultDisclaimer }
							</p>
						</div>
					</div>
				</div>
			</Disabled>
		</div>
	);
}

// not sure
export const Save = () => {
	return <div { ...useBlockProps.save() } />;
};
