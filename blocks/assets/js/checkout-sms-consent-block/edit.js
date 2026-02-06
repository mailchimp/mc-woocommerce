/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody, RadioControl, Disabled } from '@wordpress/components';
import { CheckboxControl  } from '@woocommerce/blocks-checkout';
import { useEffect, useState, useCallback } from '@wordpress/element';
import { useSelect } from '@wordpress/data';


/**
 * Internal dependencies
 */
import './style.scss';


export const Edit = ( { attributes, setAttributes } ) => {
	const { text, gdprStatus, checkboxSettings, defaultDisclaimer} = attributes;
	const blockProps = useBlockProps();

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

			<Disabled>
				<div className="wc-block-components-checkout-step__container mailchimp-sms-consent">
					<div className="wc-block-components-checkout-step__content">
						<CheckboxControl
							id="newsletter-text"
							checked={ checked }
							disabled={ true }
							style={{marginTop: 0}}
						>
							<span>{ text }</span>
						</CheckboxControl>
				{ checked && gdprStatus != 'hide' && (
					<div style={{ marginTop: '12px' }}>
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
				) }
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
