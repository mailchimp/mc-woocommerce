/**
 * External dependencies
 */
import {useCallback, useEffect, useState} from '@wordpress/element';
import { CheckboxControl, ValidatedTextInput } from '@woocommerce/blocks-checkout';
import {useSelect, useDispatch} from "@wordpress/data";
import {__} from "@wordpress/i18n";
import PhoneInputWithCountrySelect from "react-phone-number-input";
import { isValidPhoneNumber } from 'react-phone-number-input'

const Block = ( {text, userSubscribed, checkoutExtensionData, defaultDisclaimer, smsSendingCountries, userSmsSubscribed, smsEnabled, usingSmsConsent } ) => {
	const [ checked, setChecked ] = useState( false );
	const [ smsPhone, setSmsPhone ] = useState( '' );
	const [ phoneError, setPhoneError ] = useState( '' );
	const [hasSmsPhoneError, setHasSmsPhoneError] = useState( false );
	const { setExtensionData } = checkoutExtensionData;
	const { setValidationErrors, clearValidationError } = useDispatch( 'wc/store/validation' );

	// Get billing country from WooCommerce checkout store
	const billingCountry = useSelect( ( select ) => {
		const store = select( 'wc/store/cart' );
		if ( store && store.getCustomerData ) {
			const customerData = store.getCustomerData();
			return customerData?.billingAddress?.country || customerData?.shippingAddress?.country || '';
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
		if (!isValidPhoneNumber(smsPhone)) {
			return __("Invalid SMS phone number.");
		}
		return '';
	}, [ checked ] );

	// Update extension data when values change
	useEffect( () => {
		setExtensionData( 'mailchimp-sms-consent', 'smsOptin', checked );
		setExtensionData( 'mailchimp-sms-consent', 'smsPhone', smsPhone );
	}, [ checked, smsPhone, setExtensionData ] );

	// Validate phone when checkbox or phone changes
	// useEffect( () => {
	// 	const error = validatePhone( smsPhone );
	// 	setHasSmsPhoneError( error );
	// }, [ checked, smsPhone, validatePhone ] );

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
			setValidationErrors( {
				'mailchimp-sms-phone': {
					message: __( 'This country is currently not available for SMS.', 'mailchimp-for-woocommerce' ),
					hidden: true,
				}
			} );
			console.log('Resetting SMS consent to false because billing country is not eligible: ' + billingCountry);
		}
		// console.log('billing country', billingCountry);
		// console.log('countries', smsSendingCountries);
	}, [ billingCountry, isCountryEligible ] );

	// Handle phone change
	const handlePhoneChange = ( value ) => {
		setSmsPhone( value );
	};

	const handleOnBlur = () => {
		if (!isValidPhoneNumber(smsPhone)) {
			setHasSmsPhoneError("Invalid SMS phone number.");
		} else {
			setHasSmsPhoneError(null);
		}
	};

	// If SMS is not enabled, user already subscribed, or country not eligible, don't render
	if ( ! smsEnabled || userSmsSubscribed ) {
		return null;
	}

	// Hide if billing country is set and not eligible
	if ( billingCountry && ! isCountryEligible( billingCountry ) ) {
		return null;
	}

	return (
		<div className='wc-block-components-checkout-step__container' id={'sms_consent_block_frontend'}>
			<div style={{ display: !usingSmsConsent || userSubscribed ? 'none' : '' }} className='wc-block-components-checkout-step__content'>
				<CheckboxControl
					id="subscribe-to-sms"
					checked={ checked }
					onChange={ setChecked }
				>
					<span dangerouslySetInnerHTML={ {__html: text} }/>
				</CheckboxControl>

				{ checked && (
					<div className="mailchimp-sms-phone-field"
						 style={{marginTop: '12px'}}>

						<PhoneInputWithCountrySelect
							id="mailchimp-sms-phone"
							className="wc-block-components-text-input"
							defaultCountry={billingCountry || 'US'}
							countries={smsSendingCountries}
							label={__('SMS Phone Number', 'mailchimp-for-woocommerce')}
							placeholder="Enter phone number"
							value={smsPhone}
							required={true}
							onBlur={handleOnBlur}
							onChange={handlePhoneChange}/>
						{
							hasSmsPhoneError &&
							<div id="sms_phone_error_message_wrapper" className="wc-block-components-validation-error" role="alert">
								<p id="validate-error-shipping_first_name">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="-2 -2 24 24" width="24" height="24"
										 aria-hidden="true" focusable="false">
										<path
											d="M10 2c4.42 0 8 3.58 8 8s-3.58 8-8 8-8-3.58-8-8 3.58-8 8-8zm1.13 9.38l.35-6.46H8.52l.35 6.46h2.26zm-.09 3.36c.24-.23.37-.55.37-.96 0-.42-.12-.74-.36-.97s-.59-.35-1.06-.35-.82.12-1.07.35-.37.55-.37.97c0 .41.13.73.38.96.26.23.61.34 1.06.34s.8-.11 1.05-.34z"></path>
									</svg>
									<span id="sms_phone_error_message">{hasSmsPhoneError}</span>
								</p>
							</div>
						}

						<p className="mailchimp-sms-disclaimer" style={{
							fontSize: '12px',
							color: '#666',
							marginTop: '8px',
							lineHeight: '1.4'
						}}>
							{defaultDisclaimer}
						</p>
					</div>
				)}

			</div>
		</div>
	);
};

export default Block;
