/**
 * External dependencies
 */
import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import Block from './block';
import metadata from './block.json';

/**
 * Get SMS block settings
 */
const smsSettings = getSetting( 'mailchimp-sms_data', {} );

/**
 * Register the SMS consent block for checkout
 */
registerCheckoutBlock( {
	metadata,
	component: ( props ) => (
		<Block
			{ ...props }
			text={ smsSettings.smsDefaultText || '' }
			disclaimerText={ smsSettings.smsDisclaimerText || '' }
			smsStatus={ smsSettings.smsStatus || 'uncheck' }
			userSmsSubscribed={ smsSettings.userSmsSubscribed || false }
			smsEnabled={ smsSettings.smsEnabled || false }
			audienceName={ smsSettings.audienceName || '' }
			smsSendingCountries={ smsSettings.smsSendingCountries || [] }
		/>
	),
} );
