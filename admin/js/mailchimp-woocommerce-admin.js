(function( $ ) {
	'use strict';

	$( window ).load(function() {
		// show/hide wizard tabs tooltips
		$('a.wizard-tab').hover(function (e) {
			e.stopPropagation();
			$('.wizard-tab-tooltip').hide();
			$(this).find('.wizard-tab-tooltip').show();

		});

		$('a.wizard-tab').mouseleave(function (e) {
			e.stopPropagation();
			$('.wizard-tab-tooltip').hide();
			$('.wizard-tab-active .wizard-tab-tooltip').show();
		});

		// show/hide optional settings
		var optionalSettings = false;
		$('.optional-settings-button').click(function () {
			if (optionalSettings) {
				$('.optional-settings-content').slideUp();
				$(this).find('span').removeClass('active');
				optionalSettings = false;
			} else {
				$('.optional-settings-content').slideDown();
				$(this).find('span').addClass('active');
				optionalSettings = true;
			}
			

		});

		// copy log button
		$('.mc-woocommerce-copy-log-button').click(function (e) {
			e.preventDefault();
			var copyText = $('#log-content');
			var $temp = $("<textarea>");
			$("body").append($temp);
			$temp.val($(copyText).text()).select();
			/* Copy the text inside the text field */
			document.execCommand("copy");
			$temp.remove();
		});

		/*
		* Shows dialog on store disconnect
		* Change wp_http_referer URL in case of store disconnect
		*/ 
		var mailchimp_woocommerce_disconnect_done = false;
		$('#mailchimp_woocommerce_disconnect').click(function (e){
			// this is to trigger the event even after preventDefault() is issued.
			if (mailchimp_woocommerce_disconnect_done) {
				mailchimp_woocommerce_disconnect_done = false; // reset flag
				return; // let the event bubble away
			}

			e.preventDefault();

			var me = $(e.target);

			const swalWithBootstrapButtons = Swal.mixin({
				customClass: {
				  confirmButton: 'button button-primary tab-content-submit disconnect-button',
				  cancelButton: 'button button-default mc-woocommerce-resync-button disconnect-button'
				},
				buttonsStyling: false,
			})
			
			swalWithBootstrapButtons.fire({
				title: 'Are you sure?',
				text: "You are about to disconnect your store from Mailchimp.",
				type: 'warning',
				showCancelButton: true,
				confirmButtonText: 'Yes, disconnect.',
				cancelButtonText: 'No, cancel!',
				reverseButtons: true
			}).then((result) => {
				if (result.value) {
					var query = window.location.href.match(/^(.*)\&/);
					if (query){
						history.replaceState({}, "", query[1]);
						$('input[name=_wp_http_referer]').val(query[1]);
					}
					try {
						mailchimp_woocommerce_disconnect_done = true;
						me.click();
					} catch (e) {
						console.error('clicking event for disconnect failed', e);
					}
				} 
			})	
		});

		/* 
		* Change wp_http_referer URL in case of in-wizard tab change
		*/ 
		var mailchimp_woocommerce_submit_done = false;
		$('#mailchimp_woocommerce_options .tab-content-submit:not(.oauth-connect)').click(function(e){
			// this is to trigger the event even after preventDefault() is issued.
			if (mailchimp_woocommerce_submit_done) {
				mailchimp_woocommerce_submit_done = false; // reset flag
				return; // let the event bubble away
			}
			e.preventDefault();

			if ($('input[name=mailchimp_woocommerce_wizard_on]').val() == 1) {
				var query = window.location.href.match(/^(.*)\&/);
				if (query){
					history.replaceState({}, "", query[1]);
					$('input[name=_wp_http_referer]').val(query[1]);		
				}
			}
			mailchimp_woocommerce_submit_done = true;
			e.target.click();

		});

		// Mailchimp OAuth connection (tab "connect")
		$('#mailchimp_woocommerce_options #mailchimp-oauth-connect').click(function(e){
			var token = '';
			var startData = {action:'mailchimp_woocommerce_oauth_start'};
			$('#mailchimp-oauth-api-key-valid').hide();
			$('#mailchimp-oauth-error').hide();
			$('#mailchimp-oauth-waiting').show();
			
			$.post(ajaxurl, startData, function(startResponse) {
				if (startResponse.success) {
					token = JSON.parse(startResponse.data.body).token;
					openOAuthPopup(token);
				}
				else {
					console.log("Error: start response:",startResponse);
				}		
			});
		});

		function openOAuthPopup(token) {
			var domain = 'https://woocommerce.mailchimpapp.com';
					var options = {
						path: domain+'/auth/start/'+token,
						windowName: 'Mailchimp For WooCommerce OAuth',
						height: 800,
						width: 1035,
					};
					var left = (screen.width - options.width) / 2;
					var top = (screen.height - options.height) / 4;
					var window_options = 'toolbar=no, location=no, directories=no, ' +
						'status=no, menubar=no, scrollbars=no, resizable=no, ' +
						'copyhistory=no, width=' + options.width +
						', height=' + options.height + ', top=' + top + ', left=' + left +
						', domain='+domain.replace('https://', '');

			// open Mailchimp OAuth popup
			var popup = window.open(options.path, options.windowName, window_options);
					
			if (popup == null) {
				window.clearInterval(oauthInterval);
				const swalWithBootstrapButtons = Swal.mixin({
					customClass: {
					  confirmButton: 'button button-primary tab-content-submit disconnect-button',
					  cancelButton: 'button button-default mc-woocommerce-resync-button disconnect-button'
					},
					buttonsStyling: false,
				})
				
				swalWithBootstrapButtons.fire({
					type : 'error',
					title: 'Login Popup is blocked!',
					text: 'Please allow your browser to show popups for this page',
					footer: '<a href="https://mailchimp.com/help/enable-pop-ups-in-your-browser/">How to Enable Pop-ups in Your Browser</a>',
					showCancelButton: true,
					cancelButtonColor: '#d33',
					confirmButtonColor: '#7fad45',
					cancelButtonText: 'Cancel',
					confirmButtonText: 'Try again',
					reverseButtons: true
				}).then((result) => {
					if (result.value) {
						openOAuthPopup(token);
					}
				});
			}
			else {
				var oauthInterval = window.setInterval(function(){
					if (popup.closed) {
						// clear interval
						window.clearInterval(oauthInterval);

						// hide/show messages
						$('#mailchimp-oauth-error').hide();
						$('#mailchimp-oauth-waiting').hide();
						$('#mailchimp-oauth-connecting').show();
						
						// ping status to check if auth was accepted
						$.post(domain + '/api/status/' + token).done(function(statusData){
							if (statusData.status == "accepted") {
								// call for finish endpoint to retrieve access_token
								var finishData = {
									action: 'mailchimp_woocommerce_oauth_finish', 
									token: token
								}
								$.post(ajaxurl, finishData, function(finishResponse) {
									if (finishResponse.success) {
										// hide/show messages
										$('#mailchimp-oauth-error').hide();
										$('#mailchimp-oauth-connecting').hide();
										$('#mailchimp-oauth-connected').show();
										
										// get access_token from finishResponse and fill api-key field value including data_center
										var accessToken = JSON.parse(finishResponse.data.body).access_token + '-' + JSON.parse(finishResponse.data.body).data_center 
										$('#mailchimp-woocommerce-mailchimp-api-key').val(accessToken);

										// always go to next step on success, so change url of wp_http_referer
										if ($('input[name=mailchimp_woocommerce_wizard_on]').val() == 1) {
											var query = window.location.href.match(/^(.*)\&/);
											if (query){
												history.replaceState({}, "", query[1]);
												$('input[name=_wp_http_referer]').val(query[1]);		
											}
										}
										// submit api_key/access_token form 
										$('#mailchimp_woocommerce_options').submit();
									}
									else {
										console.log('Error calling OAuth finish endpoint. Data:', finishResponse);
									}
								});
							}
							else {
								$('#mailchimp-oauth-connecting').hide();
								$('#mailchimp-oauth-error').show();
								console.log('Error calling OAuth status endpoint. No credentials provided at login popup? Data:', statusData);
							}
						});
					}
				}, 250);
			}
			// While the popup is open, wait. when closed, try to get status=accepted
		}

		// Remove Initial Sync Banner oon dismiss
		$('#setting-error-mailchimp-woocommerce-initial-sync-end .notice-dismiss').click(function(e){
			$.get(phpVars.removeReviewBannerRestUrl, [], function(response){
				console.log(response);
			});
		});

		$('#comm_box_switch').change(function (e){
			var switch_button = this;
			var opt = this.checked ? 1 : 0;
			
			var data = {
				action: 'mailchimp_woocommerce_communication_status', 
				opt: opt
			}

			$('.comm_box_status').hide();
			$('#comm_box_status_' + opt).show();

			$.post(ajaxurl, data, function(response) {
				if (response.success) {
					$('#mc-comm-save').html(response.data);
					$('#mc-comm-save').css('color', '#628735').show().fadeOut(5000);
					switch_button.checked = opt;
				}
				else {
					$('#mc-comm-save').html(response.data.error);
					$('#mc-comm-save').css('color', 'red').show().fadeOut(5000);
					switch_button.checked = 1 - opt;
					$('.comm_box_status').hide();
					$('#comm_box_status_' + (1 - opt)).show();
				}
			});
		});
		// communications box radio ajax call
		$('input.comm-box-input').change(function(e){
			var data = {
				action: 'mailchimp_woocommerce_communication_status', 
				opt: this.value
			}
			var opt = this.value;
			
			$.post(ajaxurl, data, function(response) {
				if (response.success) {
					$('#mc-comm-save-'+opt).html(response.data);
					$('#mc-comm-save-'+opt).css('color', '#628735').show().fadeOut(5000);
					$('#swi').checked = true;
				}
				else {
					$('#mc-comm-save-'+opt).html(response.data.error);
					$('#mc-comm-save-'+opt).css('color', 'red').show().fadeOut(5000);
					$('#mc-comm-input-'+response.data.opt).prop('checked', true);
					$('#swi').checked = false;
				}
			});
		});

		// Account create functionality
		$('#mc-woocommerce-create-account-next').click(function () {
			var next_button = $(this);
			var spinner = $(this).next('.spinner');
			spinner.css('visibility', 'visible')
			
			$('.mc-woocommerce-create-account-step-error > p').hide();
			$('#username_suggestion').css('visibility', 'hidden');
			var email = $('input#email');
			var username = $('input#username');
			
			var isValid= true;
		
			if (! email[0].checkValidity()) {
				$('#email_error').show();
				isValid= false;
			}
			else {
				$('#email_error').hide();
			}
			
			if (! username[0].checkValidity()) {
				$('#username_invalid_error').show();
				spinner.css('visibility', 'hidden');
			}
			else {
				$('#username_invalid_error').hide();
				var data = {
					action:'mailchimp_woocommerce_create_account_check_username',
					username: username.val(),
				};

				$.post(ajaxurl, data, function(response) {
					if (response.success) {
						$('#username_exists_error').hide();
						if ( isValid == true) {
							spinner.css('visibility', 'hidden');
							$('.mc-woocommerce-settings').css('height', '900px');
							$('#mc-woocommerce-create-account-step-1').hide();
							$('#mc-woocommerce-create-account-step-2').show();
							$('#step_count').html('2');
						}
					}
					else {
						$('#username_exists_error').show();
						$('#username_suggestion').css('visibility', 'visible');
						$('#username_suggestion span').html(response.data.suggestion);
						spinner.css('visibility', 'hidden');
					}		
				});
			}
		});

		$('#mc-woocommerce-create-account-prev').click(function () {
			$('#mc-woocommerce-create-account-step-1').show();
			$('#mc-woocommerce-create-account-step-2').hide();
			$('#step_count').html('1');
			
		});

		$('#mc-woocommerce-create-account-go').click(function () {
			var email = $('input#email');
			var firstName = $('input#first_name');
			var lastName = $('input#last_name');
			var org = $('input#org');
			var timezone = $('select#timezone');

			var username = $('input#username');

			var address = $('input#address');
			var address2 = $('input#address2');
			var city = $('input#city');
			var state = $('input#state');
			var zip = $('input#zip');
			var country = $('select#country');
			var phone = $('input#phone');
			
			var isValid = true;
			
			var spinner = $(this).next('.spinner');
			spinner.css('visibility', 'visible');

			if (! address[0].checkValidity() || ! address2[0].checkValidity()) {
				$('#address_error').show();
				isValid= false;
			}
			else {
				$('#address_error').hide();
			}

			if (! city[0].checkValidity()) {
				$('#city_error').show();
				isValid= false;
			}
			else {
				$('#city_error').hide();
			}

			if (! state[0].checkValidity()) {
				$('#state_error').show();
				isValid= false;
			}
			else {
				$('#state_error').hide();
			}

			if (! zip[0].checkValidity()) {
				$('#zip_error').show();
				isValid= false;
			}
			else {
				$('#zip_error').hide();
			}

			if (! country[0].checkValidity()) {
				$('#country_error').show();
				isValid= false;
			}
			else {
				$('#country_error').hide();
			}

			if (! phone[0].checkValidity()) {
				$('#phone_error').show();
				isValid= false;
			}
			else {
				$('#phone_error').hide();
			}

			if (! timezone[0].checkValidity()) {
				$('#timezone_error').show();
				isValid= false;
			}
			else {
				$('#timezone_error').hide();
			}

			if (isValid) {
				var data = {
					action:'mailchimp_woocommerce_create_account_signup',
					data: {
						email: email.val(),
						first_name: firstName.val(),
						last_name: lastName.val(),
						org: org.val(),
						timezone: timezone.val(),
						username: username.val(),
						address: {
							address1: address.val(),
							city: city.val(),
							state: state.val(),
							zip: zip.val(),
							country: country.val()
						}
					},
				};

				// add optional address 2 only if it's filled out
				if (address2.val() != '') {
					data.data.address.address2 = address2.val();
				}

				$.post(ajaxurl, data, function(response) {
					if (response.success) {
						$('#connecting').show();
						spinner.css('visibility', 'hidden');
										
						// get access_token and fill api-key field value including data_center
						var accessToken = response.data.data.oauth_token + '-' + response.data.data.dc
						
						$('#mailchimp-woocommerce-mailchimp-api-key').val(accessToken);

						// always go to next step on success, so change url of wp_http_referer
						if ($('input[name=mailchimp_woocommerce_wizard_on]').val() == 1) {
							var query = window.location.href.match(/^(.*)\&/);
							if (query){
								history.replaceState({}, "", query[1]);
								$('input[name=_wp_http_referer]').val(query[1]);		
							}
						}
						// submit api_key/access_token form 
						$('#mailchimp_woocommerce_options').submit();
					}
				}).fail(function (err) {
					console.log('FAIL:' , err);
				});
			}
			else {
				spinner.css('visibility', 'hidden')
			}
		});

		$('#username_suggestion span').click(function (){
			$('input#username').val($(this).html());
		});

		$('#mc-woocommerce-create-account-step-1').keypress(function(event){
			event.stopPropagation();
			var keycode = (event.keyCode ? event.keyCode : event.which);
			if ( keycode == '13' ){
				$("#mc-woocommerce-create-account-next").click(); 				
			}
		});

		$('#mc-woocommerce-create-account-step-2').keypress(function(event){
			event.stopPropagation();
			var keycode = (event.keyCode ? event.keyCode : event.which);
			if ( keycode == '13' ){
				$("#mc-woocommerce-create-account-go").click(); 				
			}
		});
	});
})( jQuery );

