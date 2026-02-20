/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

const {
	optinDefaultText,
	audienceName,
	defaultDisclaimer,
	smsSendingCountries,
	userSmsSubscribed,
	smsEnabled,
	usingSmsConsent
} = getSetting( 'mailchimp-sms-consent_data', '' );

export default {
	text: {
		type: 'string',
		default: optinDefaultText,
	},
	audienceName: {
		type: 'string',
		default: audienceName,
	},
	defaultDisclaimer: {
		type: 'string',
		default: defaultDisclaimer,
	},
	userSmsSubscribed: {
		type: 'bool',
		default: userSmsSubscribed
	},
	smsEnabled: {
		type: 'bool',
		default: smsEnabled
	},
	usingSmsConsent: {
		type: 'bool',
		default: usingSmsConsent || false
	},
	smsSendingCountries: {
		type: 'array',
		default: smsSendingCountries
	}
};
