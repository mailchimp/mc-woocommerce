(function( $ ) {
	'use strict';

	$( document ).ready(function() {
		var mailchimp_woocommerce_newsletter = $('#mailchimp_woocommerce_newsletter');
		var gdprFields = $('#mailchimp-gdpr-fields');
		if (gdprFields.length) {
			showHideGDPR(mailchimp_woocommerce_newsletter, gdprFields);
			
			mailchimp_woocommerce_newsletter.change(function () {
				showHideGDPR(mailchimp_woocommerce_newsletter, gdprFields);
			});
		}

	})
	function showHideGDPR(mailchimp_woocommerce_newsletter, gdprFields) {
		if (mailchimp_woocommerce_newsletter.prop('checked') == true) {
			gdprFields.slideDown();
		}
		else {
			gdprFields.slideUp();
		}
	}
})( jQuery );