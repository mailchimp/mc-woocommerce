/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

const {
	optinDefaultText,
	gdprHeadline,
	gdprFields,
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
	}
};
