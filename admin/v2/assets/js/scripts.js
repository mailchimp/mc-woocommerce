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
	});
})( jQuery );

