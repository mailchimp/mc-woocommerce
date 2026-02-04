/**
 * External dependencies
 */
import { useEffect, useState, useCallback } from '@wordpress/element';
import { CheckboxControl, ValidatedTextInput } from '@woocommerce/blocks-checkout';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * SMS Consent Block Component
 */
const Block = ( { 
	text, 
	disclaimerText, 
	smsStatus, 
	userSmsSubscribed, 
	smsEnabled,
	audienceName,
	smsSendingCountries,
	checkoutExtensionData 
} ) => {
	const defaultChecked = smsStatus === 'check';
	const [ checked, setChecked ] = useState( defaultChecked );
	const [ smsPhone, setSmsPhone ] = useState( '' );
	const [ phoneError, setPhoneError ] = useState( '' );
	const { setExtensionData } = checkoutExtensionData;

	// Get billing country from WooCommerce checkout store
	const billingCountry = useSelect( ( select ) => {
		const store = select( 'wc/store/cart' );
		if ( store && store.getCustomerData ) {
			const customerData = store.getCustomerData();
			return customerData?.billingAddress?.country || '';
		}
		return '';
	}, [] );

	// Check if billing country is eligible for SMS
	const isCountryEligible = useCallback( ( countryCode ) => {
		// If no countries configured, allow all
		if ( ! smsSendingCountries || smsSendingCountries.length === 0 ) {
			return true;
		}
		return smsSendingCountries.includes( countryCode?.toUpperCase() );
	}, [ smsSendingCountries ] );

	// Validate phone number format
	const validatePhone = useCallback( ( phone ) => {
		if ( checked && ! phone ) {
			return __( 'Phone number is required for SMS consent.', 'mailchimp-for-woocommerce' );
		}
		if ( phone && ! /^\+?[1-9]\d{6,14}$/.test( phone.replace( /[\s\-\(\)]/g, '' ) ) ) {
			return __( 'Please enter a valid phone number.', 'mailchimp-for-woocommerce' );
		}
		return '';
	}, [ checked ] );

	// Update extension data when values change
	useEffect( () => {
		setExtensionData( 'mailchimp-sms', 'smsOptin', checked );
		setExtensionData( 'mailchimp-sms', 'smsPhone', smsPhone );
	}, [ checked, smsPhone, setExtensionData ] );

	// Validate phone when checkbox or phone changes
	useEffect( () => {
		const error = validatePhone( smsPhone );
		setPhoneError( error );
	}, [ checked, smsPhone, validatePhone ] );

	// Reset checkbox if country becomes ineligible
	useEffect( () => {
		if ( billingCountry && ! isCountryEligible( billingCountry ) ) {
			setChecked( false );
			setSmsPhone( '' );
		}
	}, [ billingCountry, isCountryEligible ] );

	// Handle phone change
	const handlePhoneChange = ( value ) => {
		setSmsPhone( value );
	};

	// If SMS is not enabled, user already subscribed, or country not eligible, don't render
	if ( ! smsEnabled || smsStatus === 'hide' || userSmsSubscribed ) {
		return null;
	}

	// Hide if billing country is set and not eligible
	if ( billingCountry && ! isCountryEligible( billingCountry ) ) {
		return null;
	}

	// Default label text
	const labelText = text || __( 'Text me with news and offers', 'mailchimp-for-woocommerce' );
	
	// Default disclaimer text with audience name placeholder
	const defaultDisclaimer = audienceName 
		? `${audienceName} – ` + __( 'By providing your phone number, you agree to receive promotional and marketing messages, notifications, and customer service communications. Message & data rates may apply. Consent is not a condition of purchase. Message frequency may vary. You can unsubscribe at any time by replying STOP.', 'mailchimp-for-woocommerce' )
		: __( 'By providing your phone number, you agree to receive promotional and marketing messages, notifications, and customer service communications. Message & data rates may apply. Consent is not a condition of purchase. Message frequency may vary. You can unsubscribe at any time by replying STOP.', 'mailchimp-for-woocommerce' );
	
	const disclaimer = disclaimerText || defaultDisclaimer;

	return (
		<div className="wc-block-components-checkout-step__container mailchimp-sms-consent">
			<div className="wc-block-components-checkout-step__content">
				<CheckboxControl
					id="subscribe-to-sms"
					checked={ checked }
					onChange={ setChecked }
				>
					<span dangerouslySetInnerHTML={ { __html: labelText } } />
				</CheckboxControl>
				
				{ checked && (
					<div className="mailchimp-sms-phone-field" style={{ marginTop: '12px', marginLeft: '28px' }}>
						<ValidatedTextInput
							id="mailchimp-sms-phone"
							type="tel"
							label={ __( 'SMS Phone Number', 'mailchimp-for-woocommerce' ) }
							value={ smsPhone }
							onChange={ handlePhoneChange }
							required={ true }
							errorMessage={ phoneError }
						/>
						<p className="mailchimp-sms-disclaimer" style={{ 
							fontSize: '12px', 
							color: '#666', 
							marginTop: '8px',
							lineHeight: '1.4'
						}}>
							{ disclaimer }
						</p>
					</div>
				) }
			</div>
		</div>
	);
};

export default Block;
