(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
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
			var copyText = $('#log-text');
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
					mailchimp_woocommerce_disconnect_done = true;
					e.target.click();
				} 
			})	
		});

		/* 
		* Change wp_http_referer URL in case of in-wizard tab change
		*/ 
		var mailchimp_woocommerce_submit_done = false;
		$('#mailchimp_woocommerce_options .tab-content-submit').click(function(e){
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
		$('#mailchimp-oauth-connect').click(function(e){
			var token = '';
			var startData = {action:'mailchimp_woocommerce_oauth_start'};
			$('#mailchimp-oauth-waiting').show();
			$.post(ajaxurl, startData, function(response) {
				if (response.success) {
					console.log("response:",response);
					token = JSON.parse(response.data.body).token;
					var domain = 'https://woocommerce.mailchimpapp.com';
					var options = {
						path: domain+'/auth/start/'+token,
						windowName: 'Mailchimp For WooCommerce OAuth',
						height: 500,
						width: 800,
					};
					var left = (screen.width - options.width) / 2;
					var top = (screen.height - options.height) / 4;
					var window_options = 'toolbar=no, location=no, directories=no, ' +
						'status=no, menubar=no, scrollbars=no, resizable=no, ' +
						'copyhistory=no, width=' + options.width +
						', height=' + options.height + ', top=' + top + ', left=' + left +
						'domain='+domain.replace('https://', '');
					
					var popup = window.open(options.path, options.windowName, window_options);
					
					var oauthInterval = window.setInterval(function(){
						if (popup.closed) {
							$('#mailchimp-oauth-waiting').hide();
							$('#mailchimp-oauth-connecting').show();
							window.clearInterval(oauthInterval);
							$.post(domain + '/api/status/' + token).done(function(statusData){
								if (statusData.status == "accepted") {
									console.log('accepted');
									var finishData = {
										action: 'mailchimp_woocommerce_oauth_finish', 
										token: token
									}
									$.post(ajaxurl, finishData, function(response) {
										if (response.success) {
											$('#mailchimp-oauth-connecting').hide();
											$('#mailchimp-oauth-connected').show();
											console.log('finish success:', response);
											var accessToken = JSON.parse(response.data.body).access_token + '-' + JSON.parse(response.data.body).data_center 
											$('#mailchimp-woocommerce-mailchimp-api-key').val(accessToken);
											var aform = $('#mailchimp_woocommerce_options');
											console.log(aform);
											aform.submit();
										}
										else {
											console.log('finish data:', response);
										}
									});
								}
								else {
									console.log('status data:', statusData.status);
								}
							});
						}
					}, 250);
					
				}
				else {
					console.log("start response:",response);
				}		
			});
		});
	});
	
})( jQuery );
