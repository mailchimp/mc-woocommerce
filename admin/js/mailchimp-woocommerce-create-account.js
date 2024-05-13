(function( $ ) {
  $(window).on('load', function() {

    // validate profile details
    let profileDetailsInputs  = $('#mc-woocommerce-profile-details input');
    let profileErrors         = getInitialErrors(profileDetailsInputs);
    let detailsValid          = validateForm(profileErrors, '#mc-woocommerce-profile-details');

    profileDetailsInputs.on('input', (e) => {
      let input = e.target

      profileErrors[input.name] = validateInput(input)
      detailsValid = validateForm(profileErrors, '#mc-woocommerce-profile-details');

      $('#mc-woocommerce-create-activate-account').attr('disabled', !detailsValid)
    })

    // validate business address
    let businessAddressInputs = $('#mc-woocommerce-business-address input, #mc-woocommerce-business-address select');
    let businessAddressErrors = getInitialErrors(businessAddressInputs);
    let businessAddressValid  = validateForm(businessAddressErrors, '#mc-woocommerce-business-address');

    businessAddressInputs.on('input', (e) => {
      let input = e.target

      businessAddressErrors[input.name] = validateInput(input)
      businessAddressValid = validateForm(businessAddressErrors, '#mc-woocommerce-business-address');

      $('#mc-woocommerce-create-activate-account').attr('disabled', !businessAddressValid)
    })

    $('#mc-woocommerce-create-activate-account').attr('disabled', !detailsValid || !businessAddressValid)

    $(document).on('click', '.js-mc-woocommerce-details-save', (e) => {
      e.preventDefault();

      if (!e.target.hasAttribute('disabled')) {
        $(e.target).closest('.mc-woocommerce-create-account-step')
            .find('.mc-woocommerce-details-wrapper')
            .removeClass('hidden');
        $(e.target).closest('.mc-woocommerce-create-account-step')
            .find('.mc-woocommerce-form-wrapper')
            .addClass('hidden');
      }
    })
    $(document).on('click', '.js-mc-woocommerce-edit-form', (e) => {
      e.preventDefault();
      $(e.target).closest('.mc-woocommerce-details-wrapper').addClass('hidden');

      $(e.target).closest('.mc-woocommerce-create-account-step')
          .find('.mc-woocommerce-form-wrapper')
          .removeClass('hidden');
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
        if (obj.name === 'email') {
          formDataObject['username'] = obj.value
        }
        return newObj
      })

      console.log(formDataObject);
      var data = {
        action:'mailchimp_woocommerce_create_account_signup',
        data: formDataObject
      };
      console.log(phpVars.ajaxurl);
      $.ajax({
        type : "post",
        dataType : "json",
        url : phpVars.ajaxurl,
        data : data,
        success: function(response) {
          console.log(response);
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
          }
        }
      });
    })
})

  // display errors and disable button in case of errors
  let validateForm = (errors, wrapperId) => {
    let valid = Object.values(errors).filter(error => error !== null).length === 0
    let inputIds = Object.keys(errors);

    inputIds.forEach(key => {
      let inputElementId = `${wrapperId} input#${key}`
      let errorElementId = `${wrapperId} #mc-woocommerce-${key}-error`

      if (errors[key] !== null) {
        $(inputElementId).closest('.box').addClass('form-error');
        $(errorElementId).text(errors[key]);
      } else {
        $(inputElementId).closest('.box').removeClass('form-error');
        $(errorElementId).text('');
      }
    })

    $(`${wrapperId} .create-account-save`).attr('disabled', !valid)

    return valid;
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
      if (!input.value.includes('@')) return "Insert correct input"
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