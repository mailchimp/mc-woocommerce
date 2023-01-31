/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { CheckboxControl } from '@woocommerce/blocks-checkout';
import {__} from "@wordpress/i18n";

const Block = ( { cart, extensions, text, gdprHeadline, gdprStatus, gdpr, checkoutExtensionData } ) => {

	let defaultGDPR = {};
	if (gdpr && gdpr.length) {
		gdpr.forEach((item) => {
			defaultGDPR[item.marketing_permission_id] = false;
		});
	}

	const status = gdprStatus === 'check';
	const [ checked, setChecked ] = useState( status );
	const [ gdprFields ] = useState({});
	const { setExtensionData } = checkoutExtensionData;
	const hideInput = gdprStatus === 'hide';

	useEffect( () => {
		setExtensionData( 'mailchimp-newsletter', 'optin', checked );
	}, [ checked, setExtensionData ] );

	return (
		<>
			<div style={{ display: gdprStatus === 'hide' ? 'none' : 'block' }}>
				<CheckboxControl
					id="subscribe-to-newsletter"
					checked={ checked }
					onChange={ setChecked }
				>
					<span dangerouslySetInnerHTML={ {__html: text} }/>
				</CheckboxControl>
				{gdpr && gdpr.length ? __(gdprHeadline, 'mailchimp-for-woocommerce') : ''}
				{gdpr && gdpr.length ? gdpr.map((gdprItem) => {
					return (<CheckboxControl
						id={'gdpr_'+gdprItem.marketing_permission_id}
						checked={ gdprFields[gdprItem.marketing_permission_id] }
						onChange={ (e) => {
							gdprFields[gdprItem.marketing_permission_id] = !gdprFields[gdprItem.marketing_permission_id];
							setExtensionData( 'mailchimp-newsletter', 'gdprFields', gdprFields);
						}}
					>
						<span dangerouslySetInnerHTML={ {__html: gdprItem.text} }/>
					</CheckboxControl>);
				}) : ''}
			</div>
		</>
	);
};

export default Block;
