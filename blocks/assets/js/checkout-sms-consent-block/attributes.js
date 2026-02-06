/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

const {
	optinDefaultText,
	gdprStatus,
	checkboxSettings,
	audienceName,
	defaultDisclaimer,
	smsSendingCountries,
	userSmsSubscribed,
	smsEnabled
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
	gdprStatus: {
		type: 'string',
		default: gdprStatus
	},
	userSmsSubscribed: {
		type: 'bool',
		default: userSmsSubscribed
	},
	smsEnabled: {
		type: 'bool',
		default: smsEnabled
	},
	checkboxSettings: {
		type: 'array',
		default: checkboxSettings
	},
	smsSendingCountries: {
		type: 'array',
		default: smsSendingCountries
	},
};
