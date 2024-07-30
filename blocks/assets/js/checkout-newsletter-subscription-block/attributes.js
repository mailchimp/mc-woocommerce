/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

const {
	optinDefaultText,
	gdprHeadline,
	gdprFields,
	gdprStatus,
	userSubscribed,
	checkboxSettings
} = getSetting( 'mailchimp-newsletter_data', '' );

export default {
	text: {
		type: 'string',
		default: optinDefaultText,
	},
	gdprHeadline: {
		type: 'string',
		default: gdprHeadline,
	},
	gdpr: {
		type: 'array',
		default: gdprFields
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
