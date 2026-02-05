/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { CheckboxControl } from '@woocommerce/blocks-checkout';

const Block = ( {text, gdprStatus, userSubscribed, checkoutExtensionData } ) => {
	const status = gdprStatus === 'check';
	const [ checked, setChecked ] = useState( status );
	const { setExtensionData } = checkoutExtensionData;

	useEffect( () => {
		setExtensionData( 'mailchimp-sms-consent', 'optin', checked );
	}, [ checked, setExtensionData ] );

	return (
		<div className='wc-block-components-checkout-step__container'>
			<div style={{ display: gdprStatus === 'hide' || userSubscribed ? 'none' : '' }} className='wc-block-components-checkout-step__content'>
				<CheckboxControl
					id="subscribe-to-sms"
					checked={ checked }
					onChange={ setChecked }
				>
					<span dangerouslySetInnerHTML={ {__html: text} }/>
				</CheckboxControl>
			</div>
		</div>
	);
};

export default Block;
