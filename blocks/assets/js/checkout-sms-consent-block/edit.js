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
	const checked = gdprStatus === 'check';

	if (gdprStatus === 'hide') return '';

	return (
		<div { ...blockProps }>
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
