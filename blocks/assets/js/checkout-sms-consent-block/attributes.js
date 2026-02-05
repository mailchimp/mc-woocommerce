/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

const {
	optinDefaultText,
	gdprStatus,
	userSubscribed,
	checkboxSettings
} = getSetting( 'mailchimp-sms-consent_data', '' );

export default {
	text: {
		type: 'string',
		default: optinDefaultText,
	},
	gdprStatus: {
		type: 'string',
		default: gdprStatus
	},
	userSubscribed: {
		type: 'bool',
		default: userSubscribed
	},
	checkboxSettings: {
		type: 'array',
		default: checkboxSettings
	}
};
