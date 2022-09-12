<?php
$user = wp_get_current_user();
$mailchimp_user_is_subscribed = ($user && $user->ID) ? get_user_meta($user->ID, 'mailchimp_woocommerce_is_subscribed', true) : false;
if (!(bool) $mailchimp_user_is_subscribed) {
	$mailchimp_my_account_label = translate( 'Subscribe to our newsletter', 'mailchimp-for-woocommerce' );
	$mailchimp_my_account = '<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">';
	$mailchimp_my_account .= '<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="mailchimp_woocommerce_is_subscribed_checkbox" id="mailchimp_woocommerce_is_subscribed" ';
	$mailchimp_my_account .= (bool) $mailchimp_user_is_subscribed ? ' checked="checked"' : '';
	$mailchimp_my_account .= '>';
	$mailchimp_my_account .= $mailchimp_my_account_label;
	$mailchimp_my_account .= '</p>';
	echo wp_kses(
		apply_filters( 'mailchimp_woocommerce_my_account_field', $mailchimp_my_account, $mailchimp_user_is_subscribed, $mailchimp_my_account_label ),
		mailchimp_expanded_alowed_tags()
	);
	if (isset($gdpr_fields) && $gdpr_fields) {
		echo wp_kses($gdpr_fields, mailchimp_expanded_alowed_tags());
	}
} else {
	echo '<input type="hidden" name="mailchimp_woocommerce_is_subscribed_checkbox" id="mailchimp_woocommerce_is_subscribed" value="1">';
}
?>