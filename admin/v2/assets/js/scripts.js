(function( $ ) {
	'use strict';

	$(window).on('load', function() {

		function debounce(func, timeout = 300){
			let timer;
			return (...args) => {
				clearTimeout(timer);
				timer = setTimeout(() => { func.apply(this, args); }, timeout);
			};
		}

		// Tags
		$('.mc-wc-tag-list .mc-wc-input').keypress(function(event) {
			var charCode = event.which || event.keyCode;
			var charStr = String.fromCharCode(charCode);
			if (/^[a-zA-Z0-9\s-_]*$/.test(charStr)) {
				return true; // Allow input
			} else {
				return false; // Prevent input
			}
		});

		$('.mc-wc-tag-list .mc-wc-input').keydown(function(event) {
			if (event.key === 'Enter') {
				event.preventDefault();
			}
		});

		$('.mc-wc-tag-list .btn-add').click(function() {
			const tag_list_current = $(this).parent().parent();
			const tag_input = tag_list_current.find('.mc-wc-input');
			const input_tag_value = $.trim(tag_input.val());
			if (input_tag_value) {
				addNewTag(tag_list_current, input_tag_value);
				tag_input.val('');
			}
		});

		function addNewTag(tag_list_ele, tag) {
			// const tags_hidden = tag_list_ele.find('#mailchimp-woocommerce-user-tags');
			const tags_hidden = $('#mailchimp-woocommerce-user-tags');
			const show_tagged = $('.mc-wc-tag-show-tagged');

			let tags_hidden_vals = tags_hidden.val();
			let tags_vals_array = [];
			let tag_vals_str = String(tag);

			// if we have tags
			if (tags_hidden_vals) {
				// split them into an array
				tags_vals_array = tags_hidden_vals.split(', ');

				// Check if the tag is in the tag list
				if ($.inArray(tag, tags_vals_array) !== -1) {
					return;
				}
			}

			tags_vals_array.push(tag);
			tag_vals_str = tags_vals_array.join(', ');

			let eleTagged = '<div><span class="mc-wc-tag-text">' + tag + '</span><span class="mc-wc-tag-icon-del" data-value="' + tag + '"></span></div>'
			if (show_tagged.html().trim() === '') {
				show_tagged.html(eleTagged);
			} else {
				show_tagged.append(eleTagged);
			}

			tags_hidden.val(tag_vals_str);

			if ($('#mailchimp_woocommerce_options .mc-wc-tab-content').length) {
				saveSettings();
			}
		}

		$('.mc-wc-tag-list').on('click', '.mc-wc-tag-icon-del', function() {
			const tag_remove = $(this).data('value');
			const tag_list_current = $(this).parent().parent().parent();
			const tag_parent_ele = $(this).parent();

			removeTag(tag_list_current, tag_parent_ele, tag_remove);
		});

		// when the audience is selected
		// $('#mailchimp_list_selector').change(function (e) {
		// 	const label = $('#mailchimp_list_selector option:selected').text();
		// 	$('.selected_audience_name').text(label);
		// });

		function removeTag(tag_list_ele, parent_ele, tag_remove) {
			const tags_hidden = tag_list_ele.find('#mailchimp-woocommerce-user-tags');
			let tags_hidden_vals = tags_hidden.val();
			let tags_vals_array = tags_hidden_vals.split(', ');
			var tag_remove_index = tags_vals_array.indexOf(String(tag_remove));
			if (tag_remove_index !== -1) {
				tags_vals_array.splice(tag_remove_index, 1);
				let tag_vals_str = tags_vals_array.join(', ');
				tags_hidden.val(tag_vals_str);
				parent_ele.remove();

				if ($('#mailchimp_woocommerce_options .mc-wc-tab-content').length) {
					saveSettings();
				}
			}
		}

		$('#mailchimp_woocommerce_options .mc-wc-tab-content input:not(.mc-wc-tag-list .mc-wc-input):not(#tower_box_switch):not(#comm_box_switch), #mailchimp_woocommerce_options .mc-wc-tab-content select:not(#log_file):not(#mailchimp-log-pref)').change(function(e) {
			e.preventDefault();

			saveSettings();
		});

		// auto save after 1.5 seconds.
		$('.opt-in-checkbox-text').keyup(debounce(function() {
			saveSettings();
		}, 1500));

		function saveSettings() {
			let formData = new FormData($('#mailchimp_woocommerce_options')[0]);
			let notice = $('.mc-wc-notice');
			let notice_content = $('#mc_notice_text');
			let content = $('.mc-wc-tab-content');

			let checkbox = $("#mailchimp_woocommerce_options").find("input[type=checkbox]");
			$.each(checkbox, function(key, val) {
				formData.append($(val).attr('name'), $(val).is(':checked') ? '1' : '0');
			});

			notice.fadeOut(1000).removeClass('error success');
			notice_content.text('');

			$.ajax({
				method: 'POST',
				url: phpVars.ajax_url_option,
				data: formData,
				processData: false,
				contentType: false,
				success: function() {
					// put the notice text in the right spot.
					notice_content.text(phpVars.l10n.option_update_success);
					notice
						.addClass('success')
						//.text(phpVars.l10n.option_update_success)
						.fadeIn();
				},
				error: function () {
					notice_content.text(phpVars.l10n.option_update_error);
					notice
						.addClass('error')
						//.text(phpVars.l10n.option_update_error)
						.fadeIn();
				},
				complete: function() {
					content.removeClass('loading');
				}
			});
		}

		// promo stuff
		const plans = {
			500: {send_up_to: 6000, regular_price: 20, promo_first_month_price: 1},
			1500: {send_up_to: 18000, regular_price: 45, promo_first_month_price: 1},
			2500: {send_up_to: 30000, regular_price: 60, promo_first_month_price: 1},
			5000: {send_up_to: 60000, regular_price: 100, promo_first_month_price: 1},
			10000: {send_up_to: 120000, regular_price: 135, promo_first_month_price: 1},
			15000: {send_up_to: 180000, regular_price: 230, promo_first_month_price: 1},
			20000: {send_up_to: 240000, regular_price: 285, promo_first_month_price: 1},
			25000: {send_up_to: 300000, regular_price: 310, promo_first_month_price: 1},
			30000: {send_up_to: 360000, regular_price: 340, promo_first_month_price: 1},
			40000: {send_up_to: 480000, regular_price: 410, promo_first_month_price: 1},
			50000: {send_up_to: 600000, regular_price: 450, promo_first_month_price: 1},
			75000: {send_up_to: 900000, regular_price: 630, promo_first_month_price: 1},
			100000: {send_up_to: 1200000, regular_price: 800, promo_first_month_price: 1}
		};

		function onPlanSelectorChanged (e) {
			const selected = $('#mailchimp_plan_selector option:selected')?.val() || 0;
			const found = plans[Number(selected)] || null;
			if (!found) {
				//console.log('plan value not found', selected);
				return;
			}
			console.log('plan found', found);
			$('.mc_contacts').text(Number(selected).toLocaleString());
			$('.mc_send_up_to').text(found.send_up_to.toLocaleString());
			$('.mc_regular_price').text('$'+found.regular_price.toString());
			$('.mc_promo_first_month_price').text('$'+found.promo_first_month_price.toString());
		}

		$('#mailchimp_plan_selector').change(onPlanSelectorChanged);

		$('.promo.mc-wc-tooltipper').on('click', function() {
			$(this).parent().find('.mc-wc-tooltipper-text').css('visibility', 'visible').fadeIn();
		});

		$('.tooltip__close').on('click', function() {
			$(this).closest('.mc-wc-tooltipper-text').fadeOut();
		});

		onPlanSelectorChanged();
	});
})( jQuery );

