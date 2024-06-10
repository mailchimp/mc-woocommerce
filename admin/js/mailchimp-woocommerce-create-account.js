(function( $ ) {
	$(window).on('load', function() {

		if ($('#mc-woocommerce-create-account input[name=signup_initiated]').val() === '1') {
			waitingForLogin();
		}
		// validate profile details
		let profileDetailsInputs  = $('#mc-woocommerce-profile-details input');
		let profileErrors         = getInitialErrors(profileDetailsInputs);
		let detailsValid          = validateForm(profileErrors, '#mc-woocommerce-profile-details');

		profileDetailsInputs.on('input', (e) => {
			let input = e.target

			$(input).closest('.box').removeClass('form-error');
			$(input).closest('.box').find('.error-field').text('');

			if (input.name === 'email' || input.name === 'confirm_email') {
				$('input#confirm_email, input#email').closest('.box').removeClass('form-error');
				$('input#confirm_email, input#email').closest('.box').find('.error-field').text('');
			}
		})

		// validate business address
		let businessAddressInputs = $('#mc-woocommerce-business-address input, #mc-woocommerce-business-address select');
		let businessAddressErrors = getInitialErrors(businessAddressInputs);
		let businessAddressValid  = validateForm(businessAddressErrors, '#mc-woocommerce-business-address');

		businessAddressInputs.on('input', (e) => {
			let input = e.target

			$(input).closest('.box').removeClass('form-error');
			$(input).closest('.box').find('.error-field').text('');
		})

		$('#mc-woocommerce-create-activate-account').click((e) => {
			e.preventDefault();

			profileDetailsInputs  = $('#mc-woocommerce-profile-details input');
			profileErrors         = getInitialErrors(profileDetailsInputs);
			detailsValid          = validateForm(profileErrors, '#mc-woocommerce-profile-details', true);

			businessAddressInputs = $('#mc-woocommerce-business-address input, #mc-woocommerce-business-address select');
			businessAddressErrors = getInitialErrors(businessAddressInputs);
			businessAddressValid  = validateForm(businessAddressErrors, '#mc-woocommerce-business-address', true);

			if (detailsValid && businessAddressValid) {
				$('.js-mc-woocommerce-activate-account').submit();
			}
		})

		$('.js-mc-woocommerce-activate-account').submit((e) => {
			e.preventDefault();
			$("#mc-woocommerce-create-activate-account").attr('disabled', true)
			$("#mc-woocommerce-create-activate-account .mc-wc-loading").removeClass('hidden')

			let formData = $(e.target).serializeArray()
			let formDataObject = {};
			formData.map(obj => {
				let newObj = {}
				formDataObject[obj.name] = obj.value
				return newObj
			})

			let postData = {
				email: formDataObject.email,
				username: formDataObject.email,
				business_name: formDataObject.business_name,
				first_name: formDataObject.first_name,
				last_name: formDataObject.last_name,
				org: formDataObject.org,
				phone_number: formDataObject.phone_number,
				timezone: formDataObject.timezone,
				address: {
					address1: formDataObject.address,
					city: formDataObject.city,
					state: formDataObject.state,
					zip: formDataObject.zip,
					country: formDataObject.country,
				}
			}
			var data = {
				action:'mailchimp_woocommerce_create_account_signup',
				data: postData
			};
			$.ajax({
				type : "post",
				dataType : "json",
				url : phpVars.ajaxurl,
				data : data,
				success: function(response) {
					$('.js-mc-woocommerce-activate-account').addClass('hidden')
					$("#mc-woocommerce-create-activate-account").attr('disabled', false)
					$("#mc-woocommerce-create-activate-account .mc-wc-loading").addClass('hidden')

					if (response.data.suggest_login) {
						$('.js-mc-woocommerce-suggest-to-login').removeClass('hidden');
						$('.js-mc-woocommerce-email').text(formDataObject.email)
						$('.mailchimp-connect').attr('href', response.data.login_link)
					} else {
						$('.js-mc-woocommerce-confirm-email').removeClass('hidden')
						$('.js-mc-woocommerce-email').text(formDataObject.email)

						waitingForLogin()
					}
				}
			});
		})
	})

	let waitingForLogin = () => {
		let loginTimeout;
		loginTimeout = window.setInterval(function() {
			var data = {
				action:'mailchimp_woocommerce_check_login_session',
			};
			$.ajax({
				type : "post",
				dataType : "json",
				url : phpVars.ajaxurl,
				data : data,
				success: function(response) {
					if (response.data.logged_in) {
						window.clearInterval(loginTimeout)
						window.location.href = response.data.redirect
					}

					if (!response.success || !response.data.success) {
						console.log(response)
					}
				}
			});
		}, 10000)
	}

	// display errors and disable button in case of errors
	let validateForm = (errors, wrapperId, displayErrors = false) => {
		let inputIds = Object.keys(errors);

		inputIds.forEach(key => {
			let inputElementId = `${wrapperId} input#${key}`
			let errorElementId = `${wrapperId} #mc-woocommerce-${key}-error`

			if (errors[key] !== null) {
				if (displayErrors) {
					$(inputElementId).closest('.box').addClass('form-error');
					$(errorElementId).text(errors[key]);
				}
			} else {
				$(inputElementId).closest('.box').removeClass('form-error');
				$(errorElementId).text('');
			}
		})
		return Object.values(errors).filter(error => error !== null).length === 0
	}

	// get errors on page load.
	let getInitialErrors = (inputs) => {
		let errors = {}
		inputs.each((index, input) => {
			errors[input.name] = validateInput(input);
		})

		return errors
	};

	// validate inputs
	let validateInput = (input) => {
		if (input.name === 'first_name') {
			if (input.value === '') return "First name can't be blank."
		}
		if (input.name === 'last_name') {
			if (input.value === '') return "Last name can't be blank."
		}
		if (input.name === 'business_name') {
			if (input.value === '') return "Business name can't be blank."
		}
		if (input.name === 'email') {
			if (input.value === '') return "Email can't be blank."
			if (!input.value.includes('@') || !input.value.includes('.')) return "Insert correct email."
			if (input.value !== $('#mc-woocommerce-profile-details input#confirm_email').val()) return "Email confirmation must match confirmation email."
		}
		if (input.name === 'confirm_email') {
			if (input.value !== $('#mc-woocommerce-profile-details input#email').val()) return "Email confirmation must match the field above."
		}

		if (input.name === 'address') {
			if (input.value === '') return "Address can't be blank."
		}
		if (input.name === 'city') {
			if (input.value === '') return "City can't be blank."
		}
		if (input.name === 'state') {
			if (input.value === '') return "State can't be blank."
		}
		if (input.name === 'zip') {
			if (input.value === '') return "Zip can't be blank."
		}

		let textElementId = `#mc-woocommerce-${input.id}-text`
		$(textElementId).text(input.value)

		return null
	}

})(window.jQuery)