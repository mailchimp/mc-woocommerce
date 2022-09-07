<?php $user = wp_get_current_user(); ?>
<?php $mailchimp_user_is_subscribed = ($user && $user->ID) ? get_user_meta($user->ID, 'mailchimp_woocommerce_is_subscribed', true) : false; ?>
<?php
$mailchimp_my_account_label = esc_html( translate( 'Subscribe to our newsletter', 'mailchimp-for-woocommerce' ) );
$mailchimp_my_account = '<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">';
$mailchimp_my_account .= '<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="mailchimp_woocommerce_is_subscribed_checkbox" id="mailchimp_woocommerce_is_subscribed" ';
$mailchimp_my_account .= !empty($mailchimp_user_is_subscribed) ? esc_attr('checked') : '';
$mailchimp_my_account .= '>';
$mailchimp_my_account .= $mailchimp_my_account_label;
$mailchimp_my_account .= translate( 'Subscribe to our newsletter', 'mailchimp-for-woocommerce' );
$mailchimp_my_account .= '</p>';
echo wp_kses_post( apply_filters( 'mailchimp_woocommerce_my_account_field', $mailchimp_my_account, $mailchimp_user_is_subscribed, $mailchimp_my_account_label ) );
?>
<?php if (isset($gdpr_fields) && $gdpr_fields) { echo wp_kses_post($gdpr_fields); } ?>

