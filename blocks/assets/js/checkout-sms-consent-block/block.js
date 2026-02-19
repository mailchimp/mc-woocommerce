/**
 * External dependencies
 */
import {useCallback, useEffect, useState} from '@wordpress/element';
import { CheckboxControl, ValidatedTextInput } from '@woocommerce/blocks-checkout';
import {useSelect, useDispatch} from "@wordpress/data";
import {__} from "@wordpress/i18n";

const Block = ( {text, gdprStatus, userSubscribed, checkoutExtensionData, defaultDisclaimer, smsSendingCountries, userSmsSubscribed, smsEnabled } ) => {
	const defaultChecked = gdprStatus === 'check';
	const [ checked, setChecked ] = useState( defaultChecked );
	const [ smsPhone, setSmsPhone ] = useState( '' );
	const [ phoneError, setPhoneError ] = useState( '' );
	const { setExtensionData } = checkoutExtensionData;
	const { setValidationErrors, clearValidationError } = useDispatch( 'wc/store/validation' );

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
		setExtensionData( 'mailchimp-sms-consent', 'smsOptin', checked );
		setExtensionData( 'mailchimp-sms-consent', 'smsPhone', smsPhone );
	}, [ checked, smsPhone, setExtensionData ] );

	// Validate phone when checkbox or phone changes
	useEffect( () => {
		const error = validatePhone( smsPhone );
		setPhoneError( error );
	}, [ checked, smsPhone, validatePhone ] );

	// Block checkout submission via validation store
	useEffect( () => {
		if ( checked && ! smsPhone ) {
			setValidationErrors( {
				'mailchimp-sms-phone': {
					message: __( 'Phone number is required for SMS consent.', 'mailchimp-for-woocommerce' ),
					hidden: true,
				}
			} );
		} else if ( checked && smsPhone && ! /^\+?[1-9]\d{6,14}$/.test( smsPhone.replace( /[\s\-\(\)]/g, '' ) ) ) {
			setValidationErrors( {
				'mailchimp-sms-phone': {
					message: __( 'Please enter a valid phone number.', 'mailchimp-for-woocommerce' ),
					hidden: true,
				}
			} );
		} else {
			clearValidationError( 'mailchimp-sms-phone' );
		}
	}, [ checked, smsPhone, setValidationErrors, clearValidationError ] );

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
	if ( ! smsEnabled || gdprStatus === 'hide' || userSmsSubscribed ) {
		return null;
	}

	// Hide if billing country is set and not eligible
	if ( billingCountry && ! isCountryEligible( billingCountry ) ) {
		return null;
	}
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

				{ checked && (
					<div className="mailchimp-sms-phone-field" style={{ marginTop: '12px', marginLeft: '28px' }}>
						<ValidatedTextInput
							id="mailchimp-sms-phone"
							type="tel"
							label={ __( 'SMS Phone Number', 'mailchimp-for-woocommerce' ) }
							value={ smsPhone }
							onChange={ handlePhoneChange }
							required={ true }
							customValidation={ ( inputObject ) => {
								const value = inputObject.value;
								if ( ! value && checked ) {
									inputObject.setCustomValidity( 'Phone number is required for SMS consent.' );
									return false;
								}
								const reg = /^\+?[1-9]\d{6,14}$/;
								if ( value && ! reg.test( value.replace( /[\s\-()]/g, '' ) ) ) {
									inputObject.setCustomValidity( 'Please enter a valid phone number.' );
									return false;
								}
								inputObject.setCustomValidity( '' );
								return true;
							} }
						/>
						<p className="mailchimp-sms-disclaimer" style={{
							fontSize: '12px',
							color: '#666',
							marginTop: '8px',
							lineHeight: '1.4'
						}}>
							{ defaultDisclaimer }
						</p>
					</div>
				) }

			</div>
		</div>
	);
};

export default Block;
