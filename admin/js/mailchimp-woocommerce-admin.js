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


	});
	
})( jQuery );
