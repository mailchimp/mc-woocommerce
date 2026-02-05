jQuery(document).ready(function($) {
    var mailchimp,
        mailchimpReady = function (a) { /in/.test(document.readyState) ? setTimeout(()=>{mailchimpReady(a)}, 9) : a(); };

    function mailchimpHandleSmsConsent(selectors, checked)
    {
        console.log('change of sms consent block', {
            ...selectors,
            checked: checked
        })
        const phoneInput = document.querySelector(selectors.phone);

        console.log('phone input', phoneInput)

        setRequired(checked, phoneInput, selectors)
    }

    function setRequired(required, phoneInput, selectors) {
        const phoneRow  = phoneInput.closest(selectors.row);
        const label     = phoneRow.querySelector(selectors.label);

        phoneInput.required = required;
        phoneInput.setAttribute('aria-required', required ? 'true' : 'false');

        // Remove existing optional/required span
        const existingSpan = label.querySelector('.optional, .required');
        if (existingSpan) {
            existingSpan.remove();
        }

        // Create correct span
        const span = document.createElement('span');
        span.className = required ? 'required' : 'optional';
        span.textContent = required ? '*' : '(optional)';
        label.textContent = label.textContent.replace('(optional)', '')
        label.appendChild(span);

        // WooCommerce validation class
        phoneRow.classList.toggle('validate-required', required);
        phoneRow.classList.remove('woocommerce-invalid', required);
        phoneRow.classList.remove('woocommerce-invalid-required-field', required);

        // Trigger WC checkout update
        document.body.dispatchEvent(new Event('update_checkout'));
    }

    function validateSmsConsent(selectors)
    {
        const countrySelector = document.querySelector(selectors.country);

        if (!countrySelector) {
            console.error('Element not found:', selectors.country);
            return;
        }

        validateCountry(countrySelector.value, selectors)

        countrySelector.addEventListener('change', (e) => {
            const value = e.target.value;

            validateCountry(value, selectors)
        });

        $(selectors.country).on('change', function (e) {
            const value = e.target.value;

            validateCountry(value, selectors)
        });
    }

    function validateCountry(country, selectors) {
        const phoneInput = document.querySelector(selectors.phone)


        if (mailchimp_public_data.sms_allowed_countries.includes(country)) {
            setRequired($(selectors.checkbox).is(':checked'), phoneInput, selectors)
            $(selectors.checkbox).closest('.form-row').show()

            console.log('country', selectors.checkbox, $(selectors.checkbox).is(':checked'))

        } else {
            setRequired(false, phoneInput, selectors)
            $(selectors.checkbox).prop('checked', false);
            $(selectors.checkbox).closest('.form-row').hide()
        }
    }

    mailchimpReady(function () {
        // if they've told us we can't do this - we have to honor it.
        if (!mailchimp_public_data.allowed_to_set_cookies) return;

        // if we're not using carts - don't bother setting any of this.
        if (mailchimp_public_data.disable_carts) return;

        var smsConsent = document.querySelector('#mailchimp_woocommerce_sms_consent');
        var blockSmsConsent = document.querySelector("#subscribe-to-sms");


        if (smsConsent) {
            let selectors = {
                country: '#billing_country',
                checkbox: '#mailchimp_woocommerce_sms_consent',
                phone: '#billing_phone',
                row: '.form-row',
                label: 'label'
            }
            validateSmsConsent(selectors)
            smsConsent.onchange = function(e) {
                mailchimpHandleSmsConsent(selectors, e.target.checked);
            }
        } else if (blockSmsConsent) {
            let phoneSelector = document.querySelector('#billing_phone') ? '#billing-phone' : '#shipping-phone'
            let countrySelector = document.querySelector('#billing_country') ? '#billing-country' : '#shipping-country'

            let selectors = {
                country: countrySelector,
                checkbox: '#subscribe-to-sms',
                phone: phoneSelector,
                row: '.wc-block-components-text-input',
                label: 'label'
            }
            validateSmsConsent(selectors)

            blockSmsConsent.onchange = function(e) {
                mailchimpHandleSmsConsent(selectors,  e.target.checked);
            }
        }
    })
})
